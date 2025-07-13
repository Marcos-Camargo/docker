<?php

require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Job responsável por corrigir o problema de sincronização de produtos com a VTEX.
 *
 */
class FixVtexProductSyncJob extends GenericBatch
{
    public function __construct()
    {
        parent::__construct();

        $batchUserSession = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'logged_in' => TRUE
        );

        $this->session->set_userdata($batchUserSession);
    }

    public function run()
    {
        $this->startJob(__FUNCTION__, null, null);
        echo "Run job " . __CLASS__ . PHP_EOL;

        $products = $this->getProductsWithSingleVariant();

        if (empty($products)) {
            echo "Nenhum produto com uma única variação encontrado." . PHP_EOL;
            $this->endJob();
            return;
        }

        echo count($products) . " produto(s) encontrados para reprocessamento." . PHP_EOL;

        foreach ($products as $product) {
            $this->processProduct($product);
        }

        echo "[" . date('Y-m-d H:i:s') . "] Finalizando job " . __CLASS__ . PHP_EOL;
        $this->endJob();
    }

    private function getProductsWithSingleVariant()
    {
        $query = "
            SELECT pv3.prd_id, pv3.variant, pv3.name
                FROM prd_variants pv3
            WHERE pv3.prd_id NOT IN (
                SELECT pv2.prd_id
                    FROM prd_variants pv2
                WHERE pv2.prd_id = pv3.prd_id AND pv2.variant = 0
            )
                AND pv3.prd_id NOT IN (
                    SELECT pv1.prd_id
                        FROM prd_variants pv1
                    WHERE pv1.prd_id IN (
                        SELECT pv.prd_id
                            FROM prd_variants pv
                        WHERE pv.prd_id NOT IN (
                            SELECT pv2.prd_id
                                FROM prd_variants pv2
                            WHERE pv2.prd_id = pv.prd_id 
                            AND pv2.variant = 0))
            GROUP BY pv1.prd_id
            HAVING COUNT(pv1.prd_id) > 1)
        ";

        return $this->db->query($query)->result_array();
    }

    /**
     * Processa um único produto:
     * - Atualiza variant para 0 nas 3 tabelas
     * - Insere na fila de publicação
     */
    private function processProduct($product)
    {
        $prdId = (int) $product['prd_id'];
        echo "➡️ Processando produto ID: {$prdId} - {$product['name']}" . PHP_EOL;

        $this->updateVariantInTable('prd_variants', $prdId);
        $this->updateVariantIfExists('prd_to_integration', $prdId);
        $this->updateVariantIfExists('vtex_ult_envio', $prdId);
        $this->insertIntoQueueIfNotExists($prdId);

        echo "✅ Produto ID {$prdId} processado com sucesso." . PHP_EOL;
    }

    /**
     * Atualiza a coluna variant = 0 onde prd_id for igual.
     */
    private function updateVariantInTable($table, $prdId)
    {
        $this->db->where('prd_id', $prdId)->update($table, ['variant' => 0]);
    }

    /**
     * Atualiza variant = 0 se o registro existir na tabela.
     */
    private function updateVariantIfExists($table, $prdId)
    {
        $exists = $this->db->where('prd_id', $prdId)->get($table)->num_rows();
        if ($exists > 0) {
            $this->updateVariantInTable($table, $prdId);
        }
    }

    /**
     * Insere o produto na fila queue_products_marketplace se ainda não estiver lá.
     */
    private function insertIntoQueueIfNotExists($prdId)
    {
        $exists = $this->db
            ->where('prd_id', $prdId)
            ->get('queue_products_marketplace')
            ->num_rows();

        if ($exists === 0) {
            $this->db->insert('queue_products_marketplace', [
                'status' => 0,
                'prd_id' => $prdId
            ]);
        }
    }
}
