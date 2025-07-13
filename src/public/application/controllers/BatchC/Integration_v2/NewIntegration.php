<?php

class NewIntegration extends BatchBackground_Controller
{
    /**
     * @var string $nameIntegration Nome da nova integração.
     */
    private $nameIntegration;

    /**
     * Instantiate a new NewIntegration instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('FileDir');
    }

    protected function handlePath(string $path): string
    {
        $basePath = APPPATH;
        if (strpos($basePath, $path) === false) {
            $path = "{$basePath}{$path}";
        }
        return FileDir::handleWithDirSeparator($path);
    }

    /**
     * @param   string  $name   Nome da nova integração.
     * @return  void
     */
    public function new(string $name)
    {
        $this->setNameIntegration($name);

        if (preg_match('/[^\w]/', $this->nameIntegration)) {
            echo "Não foi possível criar a integradora ($this->nameIntegration). Todas as integradoras deverão ter o nome em minúsculo e são aceitas apenas letras, números e underline.";
            return;
        }

        if (file_exists($this->handlePath("controllers/Api/Integration_v2/{$this->nameIntegration}"))) {
            echo "Não foi possível criar a integradora ($this->nameIntegration). Já existem pastas/arquivos configurados. O processo deverá ser feito manual.";
            return;
        }

        try {
            $this->createTools();
            $this->createPathApi();
            $this->createBatch();
            $this->createConfigLib();
            $this->createConfigRequestIntegration();
        } catch (Exception $exception) {
            echo "Não foi possível criar os arquivos\n{$exception->getMessage()}";
        }

        echo "Arquivos da integradora $this->nameIntegration, criados com sucesso\n";
    }

    /**
     * Define o nome da nova integração.
     *
     * @param   string  $name   Nome da nova integração.
     * @return  void
     */
    public function setNameIntegration(string $name)
    {
        // Todas as integradoras terão o nome em minúsculo.
        $this->nameIntegration = strtolower($name);
    }

    /**
     * Cria arquivos na biblioteca.
     *
     * @return  void
     * @throws  Exception
     */
    private function createTools()
    {
        try {
            // Tools Order
            $file = file($this->handlePath('controllers/BatchC/Integration_v2/Example/libraries/ToolsOrder.php'));

            $content = implode('', $file);
            $content = str_replace("namespace Integration\NEW_INTEGRATION;", "namespace Integration\\$this->nameIntegration;", $content);

            if (!file_exists($this->handlePath("libraries/Integration_v2/{$this->nameIntegration}"))) {
                mkdir($this->handlePath("libraries/Integration_v2/{$this->nameIntegration}"), 0777, true);
            }

            $file = fopen($this->handlePath( "libraries/Integration_v2/{$this->nameIntegration}/ToolsOrder.php"), 'w');
            fwrite($file, $content);
            fclose($file);

            // Tools Product
            $file = file($this->handlePath('controllers/BatchC/Integration_v2/Example/libraries/ToolsProduct.php'));

            $content = implode('', $file);
            $content = str_replace(
                "namespace Integration\Integration_v2\NEW_INTEGRATION;",
                "namespace Integration\Integration_v2\\$this->nameIntegration;",
                $content
            );

            if (!file_exists($this->handlePath("libraries/Integration_v2/{$this->nameIntegration}"))) {
                mkdir($this->handlePath("libraries/Integration_v2/{$this->nameIntegration}"), 0777, true);
            }

            $file = fopen($this->handlePath("libraries/Integration_v2/{$this->nameIntegration}/ToolsProduct.php"), 'w');
            fwrite($file, $content);
            fclose($file);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Cria pasta em API.
     *
     * @return  void
     * @throws  Exception
     */
    private function createPathApi()
    {
        try {
            if (!file_exists($this->handlePath("controllers/Api/Integration_v2/{$this->nameIntegration}"))) {
                mkdir($this->handlePath("controllers/Api/Integration_v2/{$this->nameIntegration}"), 0777, true);
            }
            copy($this->handlePath('controllers/BatchC/Integration_v2/Example/Api/UpdateNFe.php'),
                $this->handlePath("controllers/Api/Integration_v2/{$this->nameIntegration}/UpdateNFe.php"));
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Cria arquivos em BatchC.
     *
     * @return  void
     * @throws  Exception
     */
    private function createBatch()
    {
        try {
            // CREATE PRODUCT
            $file = file($this->handlePath('controllers/BatchC/Integration_v2/Example/Batch/CreateProduct.php'));

            $content = implode('', $file);
            $content = str_replace(
                "use Integration\Integration_v2\NEW_INTEGRATION\ToolsProduct;",
                "use Integration\Integration_v2\\$this->nameIntegration\ToolsProduct;",
                $content);
            $content = str_replace(
                "require APPPATH . \"libraries/Integration_v2/NEW_INTEGRATION/ToolsProduct.php\";",
                "require APPPATH . \"libraries/Integration_v2/$this->nameIntegration/ToolsProduct.php\";",
                $content);

            if (!file_exists($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}"))) {
                mkdir($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}"), 0777, true);
            }

            $file = fopen($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}/CreateProduct.php"), 'w');
            fwrite($file, $content);
            fclose($file);


            // UPDATE PRICE STOCK
            $file = file($this->handlePath('controllers/BatchC/Integration_v2/Example/Batch/UpdatePriceStock.php'));

            $content = implode('', $file);
            $content = str_replace(
                "use Integration\Integration_v2\NEW_INTEGRATION\ToolsProduct;",
                "use Integration\Integration_v2\\$this->nameIntegration\ToolsProduct;",
                $content);
            $content = str_replace(
                "require APPPATH . \"libraries/Integration_v2/NEW_INTEGRATION/ToolsProduct.php\";",
                "require APPPATH . \"libraries/Integration_v2/$this->nameIntegration/ToolsProduct.php\";",
                $content);

            if (!file_exists($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}"))) {
                mkdir($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}"), 0777, true);
            }

            $file = fopen($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}/UpdatePriceStock.php"), 'w');
            fwrite($file, $content);
            fclose($file);


            // UPDATE PRODUCT
            $file = file($this->handlePath('controllers/BatchC/Integration_v2/Example/Batch/UpdateProduct.php'));

            $content = implode('', $file);
            $content = str_replace(
                "use Integration\Integration_v2\NEW_INTEGRATION\ToolsProduct;",
                "use Integration\Integration_v2\\$this->nameIntegration\ToolsProduct;",
                $content);
            $content = str_replace(
                "require APPPATH . \"libraries/Integration_v2/NEW_INTEGRATION/ToolsProduct.php\";",
                "require APPPATH . \"libraries/Integration_v2/$this->nameIntegration/ToolsProduct.php\";",
                $content);

            if (!file_exists($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}"))) {
                mkdir($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}"), 0777, true);
            }

            $file = fopen($this->handlePath("controllers/BatchC/Integration_v2/Product/{$this->nameIntegration}/UpdateProduct.php"), 'w');
            fwrite($file, $content);
            fclose($file);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Cria configuração em biblioteca;
     *
     * @return  void
     * @throws  Exception
     */
    private function createConfigLib()
    {
        try {
            // Tools Order
            $file = file($this->handlePath('libraries/Integration_v2/Integration_v2.php'));

            $content = implode('', $file);
            $content = str_replace('
            // NÃO REMOVER!
            case \'NEW_INTEGRATION\':
                $options[\'headers\'][\'API_KEY\'] = $this->credentials->API_KEY;
                break;',
                '
            case \''.$this->nameIntegration.'\':
                $options[\'headers\'][\'API_KEY\'] = $this->credentials->API_KEY_'.$this->nameIntegration.';
                break;
            // NÃO REMOVER!
            case \'NEW_INTEGRATION\':
                $options[\'headers\'][\'API_KEY\'] = $this->credentials->API_KEY;
                break;',
                $content);

            $file = fopen($this->handlePath("libraries/Integration_v2/Integration_v2.php"), 'w');
            fwrite($file, $content);
            fclose($file);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Cria solicitação de integração.
     *
     * @return  void
     * @throws  Exception
     */
    private function createConfigRequestIntegration()
    {
        try {
            // Tools Order
            $file = file($this->handlePath('views/stores/integration.php'));

            $content = implode('', $file);
            $content = str_replace('
<div id="MODALNEWINTEGRATION"></div>',
                '
<div class="modal fade" tabindex="-1" role="dialog" id="integration_'.$this->nameIntegration.'">
    <div class="modal-dialog" role="document">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line(\'application_integration\') . \' '.$this->nameIntegration.'\'?></span></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Token</label>
                                <input type="text" class="form-control" name="token" value="" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Webhook Nota Fiscal</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?=base_url("Api/Integration_v2/'.$this->nameIntegration.'/UpdateNFe?apiKey={$dataIntegration[\'token_callback\']}")?>" readonly>
                                    <span class="input-group-btn">
                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal"><?=$this->lang->line(\'application_close\');?></button>
                    <button type="submit" class="btn btn-success col-md-4"><?=$this->lang->line(\'application_send\');?></button>
                </div>
                <input type="hidden" name="integration" value="'.$this->nameIntegration.'">
                <input type="hidden" name="store" value="<?=$storeId?>">
            </div>
        </form>
    </div>
</div>
<div id="MODALNEWINTEGRATION"></div>',
                $content);

            $content = str_replace('
    .box-widget.NEWINTEGRATION .widget-user-header{
        background-color: #FFFFFF;
    }',
                '
    .box-widget.'.$this->nameIntegration.' .widget-user-header{
        background-color: #008800;
    }
    .box-widget.NEWINTEGRATION .widget-user-header{
        background-color: #FFFFFF;
    }',
                $content);

            $file = fopen($this->handlePath("views/stores/integration.php"), 'w');
            fwrite($file, $content);
            fclose($file);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}