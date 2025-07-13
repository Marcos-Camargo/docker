<?php

class Model_gateway_transfers extends CI_Model
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @param int $transfer_id
     * @param int $gateway_id
     * @param string $receiver
     * @param int $amount
     * @param string $transfer_type
     * @param string|null $sender
     * @return int|null
     */
    public function createPreTransfer(int $transfer_id,
                                      int $gateway_id,
                                      string $receiver,
                                      int $amount,
                                      string $transfer_type,
                                      string $sender = null): ?int
    {

        $data = [
            'transfer_id' => $transfer_id,
            'gateway_id' => $gateway_id,
            'sender_id' => $sender,
            'receiver_id' => $receiver,
            'amount' => $amount,
            'transfer_type' => $transfer_type,
            'status' => 'CREATING'
        ];

        return $this->db->insert('gateway_transfers', $data) ? $this->db->insert_id() : null;

    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function saveTransfer(int $id, array $data): bool
    {

        return $this->db->update('gateway_transfers', $data, ['id' => $id]);

    }

}
