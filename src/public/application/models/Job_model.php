<?php

class Job_model extends CI_Model
{

    public function reschedule($id)
    {
        $this->db->where('id', $id)
            ->update('jobs', [
                'reserved_at' => null,
                'available_at' => date('Y-m-d H:i:s', strtotime('+5 seconds'))
            ]);
    }

    public function markAsFailed($id)
    {
        $this->db->where('id', $id)
            ->update('jobs', [
                'reserved_at' => date('Y-m-d H:i:s'), // Marca como reservado pra nunca mais pegar
                'available_at' => date('9999-12-31 23:59:59') // Nunca vai ficar disponível
            ]);
    }

    public function add($jobClass, $serializedJob, $queue = 'default', $classHash = null, $availableAt = null,
        $payloadHash = null, $reservedTimeoutSeconds = 300, $maxAttempts = 5, $retryDelaySeconds = 600,
        $attemps = 0, $failedAt = null, $failedReason = null)
    {
        if ($payloadHash) {
            $exists = $this->db->where('payload_hash', $payloadHash)
                ->where('queue !=', 'failed')
                ->group_start()
                ->where('reserved_at IS NULL')
                ->or_where('available_at <=', date('Y-m-d H:i:s'))
                ->group_end()
                ->get('jobs')
                ->row();

            if ($exists) {
                return false;
            }
        }

        return $this->db->insert('jobs', [
            'payload' => $serializedJob,
            'payload_hash' => $payloadHash,
            'queue' => $queue,
            'class_hash' => $classHash,
            'reserved_timeout_seconds' => $reservedTimeoutSeconds,
            'max_attempts' => $maxAttempts,
            'attempts' => $attemps,
            'retry_delay_seconds' => $retryDelaySeconds,
            'available_at' => $availableAt ?: date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'failed_at' => $failedAt,
            'failed_reason' => $failedReason,
        ]);
    }


    public function getNextJob($queue = null)
    {
        $this->db->trans_start();

        $now = date('Y-m-d H:i:s');

        $this->db->where('available_at <=', $now)
            ->where('queue !=', 'failed');

        if ($queue) {
            $this->db->where('queue', $queue);
        }

        // Condição para reserved_at:
        $this->db->group_start();
        $this->db->where('reserved_at IS NULL');
        $this->db->or_where('ADDTIME(reserved_at, SEC_TO_TIME(COALESCE(reserved_timeout_seconds, 300))) <=', $now);
        $this->db->group_end();

        $job = $this->db
            ->order_by('id', 'asc')
            ->limit(1)
            ->get('jobs')
            ->row();

        if ($job) {
            $this->db->where('id', $job->id)
                ->update('jobs', [
                    'reserved_at' => $now,
                    'attempts' => $job->attempts + 1,
                ]);
        }

        $this->db->trans_complete();
        return $job;
    }

    public function unlock($id, $attempts = 0, $delaySeconds = 0)
    {

        $availableAt = date('Y-m-d H:i:s', strtotime("+{$delaySeconds} seconds"));

        $changes = [
            'reserved_at' => null,
            'available_at' => $availableAt
        ];

        if ($attempts > 0) {
            $changes['attempts'] = $attempts;
        }

        return $this->db->where('id', $id)
            ->update('jobs', $changes);
    }

    public function moveToFailedQueue($id, $errorMessage = null)
    {
        return $this->db->where('id', $id)
            ->update('jobs', [
                'queue' => 'failed',
                'reserved_at' => null,
                'available_at' => date('Y-m-d H:i:s'),
                'failed_reason' => $errorMessage,
                'failed_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function getFailedJobs()
    {
        return $this->db
            ->select('id, payload, attempts, failed_reason, failed_at')
            ->select("(CASE WHEN payload IS NOT NULL THEN 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(payload, '\"', 3), '\"', -1) 
                ELSE NULL END) AS class_name", false)
            ->where('queue', 'failed')
            ->order_by('failed_at', 'desc')
            ->get('jobs')
            ->result();
    }

    public function getFailedJobById($id)
    {
        return $this->db
            ->where('id', $id)
            ->where('queue', 'failed')
            ->get('jobs')
            ->row();
    }

    public function reenqueueFailedJob($job)
    {
        return $this->db->where('id', $job->id)
            ->update('jobs', [
                'queue' => 'default', // volta para fila default ou ajuste se quiser
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function flushFailedJobs()
    {
        $this->db->where('queue', 'failed');
        $this->db->delete('jobs');
        return $this->db->affected_rows();
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete('jobs');
    }

    public function getQueueStats()
    {
        $now = date('Y-m-d H:i:s');

        // Subquery para pegar stucks com base em reserved_timeout_seconds
        $stuckJobs = $this->db->select('COUNT(*) as total')
            ->where('reserved_at IS NOT NULL')
            ->where("ADDTIME(reserved_at, SEC_TO_TIME(COALESCE(reserved_timeout_seconds, 300))) <=", $now)
            ->get_compiled_select('jobs', true);

        // Subquery para pegar reservados válidos
        $activeJobs = $this->db->select('COUNT(*) as total')
            ->where('reserved_at IS NOT NULL')
            ->where("ADDTIME(reserved_at, SEC_TO_TIME(COALESCE(reserved_timeout_seconds, 300))) >", $now)
            ->get_compiled_select('jobs', true);

        // Subquery para pendentes (ainda não reservados)
        $pendingJobs = $this->db->select('COUNT(*) as total')
            ->where('reserved_at IS NULL')
            ->where('available_at <=', $now)
            ->get_compiled_select('jobs', true);

        // Subquery para falhados
        $failedJobs = $this->db->select('COUNT(*) as total')
            ->where('queue', 'failed')
            ->get_compiled_select('jobs', true);

        // Total geral
        $totalJobs = $this->db->count_all('jobs');

        return [
            'total' => $totalJobs,
            'pending' => $this->db->query($pendingJobs)->row()->total,
            'reserved' => $this->db->query($activeJobs)->row()->total,
            'stuck' => $this->db->query($stuckJobs)->row()->total,
            'failed' => $this->db->query($failedJobs)->row()->total,
        ];
    }

}
