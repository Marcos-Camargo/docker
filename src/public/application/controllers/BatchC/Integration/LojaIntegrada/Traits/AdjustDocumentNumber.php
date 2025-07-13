<?php
if (!defined('AdjustDocumentNumber')) {
    define('AdjustDocumentNumber', '');
    trait AdjustDocumentNumber
    {
        private function getOnlyNumberOnDocumentNumber($documentNumber)
        {
            if (preg_match('/(\d{3}).(\d{3}).(\d{3})-(\d{2})/i', $documentNumber)) {
                $documentNumber = preg_replace('/(\d{3}).(\d{3}).(\d{3})-(\d{2})/i', '$1$2$3$4', $documentNumber);
            }
            if (preg_match('/(\d{2}).(\d{3}).(\d{3})\/(\d{4})-(\d{2})/i', $documentNumber)) {
                $documentNumber = preg_replace('/(\d{2}).(\d{3}).(\d{3})\/(\d{4})-(\d{2})/i', '$1$2$3$4$5$', $documentNumber);
            }
            return $documentNumber;
        }
        private function getTypeOnDocumentNumber($documentNumber)
        {
            if (preg_match('/(\d{3}).(\d{3}).(\d{3})-(\d{2})/i', $documentNumber)) {
                return 'CPF';
            }
            if (preg_match('/(\d{2}).(\d{3}).(\d{3})\/(\d{4})-(\d{2})/i', $documentNumber)) {
                return 'CNPJ';
            }
            if (preg_match('/(\d{11})/i', $documentNumber)) {
                return 'CPF';
            }
            if (preg_match('/(\d{14})/i', $documentNumber)) {
                return 'CNPJ';
            }
        }
        private function formatDocumentNumber($documentNumber){
            if (strlen($documentNumber)==11) {
                return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/i', '$1.$2.$3-$4', $documentNumber);
            }
            if (strlen($documentNumber)==14) {
                return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/i', '$1.$2.$3/$4-$5', $documentNumber);
            }
            return $documentNumber;
        }
    }
}
