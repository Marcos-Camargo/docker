<?php

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_stores $model_stores
 */
class Collection extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_stores');
    }

    /**
     * @param $type
     * @param $sellercenter
     * @return array
     */
    public function generateRequiredColumnsByType(): array
    {
        $columns = [];
        // $columns[] = 'ID da Loja';
        // $columns[] = 'Sku do Produto';
        $columns[] = 'Navegacao';

        return $columns;
    }

    public function downloadCollections()
    {

        // $stores = $this->model_stores->getMyCompanyStoresArrayIds();

        $this->db->select('mktp_id');

        // if (!empty($stores)) {
        //     $this->db->where_in('store_id', $stores);
        // }

        // $this->db->join('products_collections', 'products_collections.collection_id = collections.id');
        // $this->db->join('products', 'products.id = products_collections.product_id');

        $collections = $this->db->get('collections')->result_array();

        $columns = $this->generateRequiredColumnsByType();

        $data = array(
            $columns,
        );

        foreach ($collections as $collection) {

            $row = [];

            // $row[] = $collection['store_id'];
            // $row[] = $collection['sku'];
            $row[] = $collection['mktp_id'];

            $data[] = $row;

        }

        $filename = 'collection_' . uniqid() . '.csv';

        $file = fopen(sys_get_temp_dir() . '/' . $filename, 'w');

        foreach ($data as $row) {
            fputcsv($file, $row, ';');
        }

        fclose($file);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        readfile(sys_get_temp_dir() . '/' . $filename);

    }

    public function uploadFile()
    {

        $path = 'assets/images/collections/';
        if (!is_dir($path)) {
            @mkdir($path, 0775);
        }

        $config['upload_path'] = $path;
        $config['allowed_types'] = 'csv';
        $config['encrypt_name'] = true;
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            exit(json_encode(['status' => false, 'errors' => $this->upload->display_errors()]));
        } else {

            $filename = $this->upload->data('file_name');

            $csv_lines = file("{$path}{$filename}");
            if(count($csv_lines) > 100000){
                unlink("{$path}{$filename}");
                exit(json_encode(['status' => false, 'message' => 'O arquivo possui mais de 100000 (cem mil) linhas. Só é permitido o envio de arquivos com 100000 (cem mil) linhas ou menos.']));
            }

            $csvToVerification = array(
                'upload_file' => "{$path}{$filename}",
                'user_id' => $this->session->userdata('id'),
                'username' => $this->session->userdata('username'),
                'user_email' => $this->session->userdata('email'),
                'usercomp' => $this->session->userdata('usercomp') ?? 1,
                'allow_delete' => true,
                'module' => 'Collection',
                'form_data' => json_encode($this->postClean()),
                'store_id' => null
            );

            if ($this->model_csv_to_verifications->create($csvToVerification)) {
                exit(json_encode(['status' => true]));
            }

            exit(json_encode(['status' => true]));

        }

    }
}