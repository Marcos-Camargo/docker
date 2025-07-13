<?php

class Model_banks extends CI_Model
{
    const TABLE = "banks";
    public function __construct()
    {
        parent::__construct();
    }

    public function getNamesBanks()
    {
        $result_bank = [];
        $banks = $this->db->select('*')->from(Model_banks::TABLE)->where(['active' => 1])->get()->result_array();
        foreach ($banks as $key => $bank) {
            $result_bank[] = $bank['name'];
        }
        return $result_bank;
    }

    public function getBanks()
    {
        $result_bank = [];
        $banks = $this->db->select('*')->from(Model_banks::TABLE)->where(['active' => 1])->get()->result_array();
        foreach ($banks as $key => $bank) {
            $result_bank[] = $bank;
        }
        return $result_bank;
    }
    public function create($data)
    {
        return $this->db->insert(Model_banks::TABLE, $data);

    }
    public function update($id, $data)
    {
        return $this->db->update(Model_banks::TABLE, $data, ['id' => $id]);
    }
    public function getBankById($id)
    {
        return $this->db->select('*')->from(Model_banks::TABLE)->where(['id' => $id])->get()->row_array();
    }
    public function getAllBanksData($length, $offset, $order, $where = [], $search = '')
    {
        $or_where = [
            'name'=>$search,
            'number'=>$search,
        ];
        return $this->db->select('*')->from(Model_banks::TABLE)->where($where)->limit($length)->offset($offset)->order_by($order['column'], $order['dir'])->group_start()->or_like($or_where)->group_end()->get()->result_array();
    }
    public function getAllBanksDataCount($length, $offset, $order, $where = [], $search = '')
    {
        $or_like = [
            'name'=>$search,
            'number'=>$search,
        ];
        return $this->db->select('*')->from(Model_banks::TABLE)->where($where)->group_start()->or_like($or_like)->group_end()->get()->num_rows();
    }
    public function getCount($where = [])
    {
        return $this->db->select('*')->from(Model_banks::TABLE)->where($where)->get()->num_rows();
    }

    public function getBankNumber($bank = null)
    {
        if ($bank === null) return false;

        $queryBank = $this->db->select('number')->from(Model_banks::TABLE)->where('name', $bank)->get();

        // não encontrou o banco ... no banco D:
        if ($queryBank->num_rows() === 0) return false;

        $rowBank = $queryBank->row_array();

        return $rowBank['number'];

        /*
         * Será consultado diretamente no banco de dados.
         *
        $banco = array();
        $banco['Banco do Brasil'] = '001';
        $banco['Santander'] = '033';
        $banco['Caixa Econômica'] = '104';
        $banco['Bradesco'] = '237';
        $banco['Next'] = '237';
        $banco['Itaú'] = '341';
        $banco['Banrisul'] = '041';
        $banco['Sicredi'] = '748';
        $banco['Sicoob'] = '756';
        $banco['Inter'] = '077';
        $banco['BRB'] = '070';
        $banco['Via Credi'] = '085';
        $banco['Neon/Votorantim'] = '655';
        $banco['Nubank'] = '260';
        $banco['Pagseguro'] = '290';
        $banco['Banco Original'] = '212';
        $banco['Safra'] = '422';
        $banco['Modal'] = '746';
        $banco['Banestes'] = '021';
        $banco['Unicred'] = '136';
        $banco['Money Plus'] = '274';
        $banco['Mercantil do Brasil'] = '389';
        $banco['JP Morgan'] = '376';
        $banco['Gerencianet Pagamentos do Brasil'] = '364';
        $banco['Banco C6'] = '336';
        $banco['BS2'] = '218';
        $banco['Banco Topazio'] = '082';
        $banco['Uniprime'] = '099';
        $banco['Banco Stone'] = '197';
        $banco['Banco Daycoval'] = '707';
        $banco['Banco Rendimento'] = '633';
        $banco['Banco do Nordeste'] = '004';
        $banco['Citibank'] = '745';
        $banco['PJBank'] = '301';
        $banco['Cooperativa Central de Credito Noroeste Brasileiro'] = '97';
        $banco['Uniprime Norte do Paraná'] = '084';
        $banco['Global SCM'] = '384';
        $banco['Cora'] = '403';
        $banco['Mercado Pago'] = '323';
        $banco['Banco da Amazonia'] = '003';
        $banco['BNP Paribas Brasil'] = '752';
        $banco['Juno'] = '383';
        $banco['Cresol'] = '133';
        $banco['BRL Trust DTVM'] = '173';
        $banco['Banco Banese'] = '047';

        if ($bank != null) {
            if (array_key_exists($bank, $banco)) {
                return $banco[$bank];
            } else {
                return false;
            }
        }
        */
    }
    public function existBankWithThisNumber($number, $id = null)
    {
        $where = [
            'number' => $number,
        ];
        if ($id) {
            $where['id !='] = $id;
        }
        return $this->db->select('*')->from(Model_banks::TABLE)->where($where)->get()->num_rows() != 0;
    }

    public function getBankNames()
    {
        $result_bank = array();
        $banks = $this->db->select('name')->from(Model_banks::TABLE)->where(['active' => 1])->get()->result_array();
        foreach ($banks as $key => $bank) {
            $result_bank[] = $bank['name'];
        }
        return $result_bank;
    }
}
