<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        if (!$this->dbforge->register_exists('errors', 'name', 'imagem')){
            $this->db->query("INSERT INTO `errors` (`name`, `status`, `msg`, `icon`) VALUES ('imagem', 1,'Reprovado por imagem inválida', 'fa fa-camera icon-color')");
        }
        if (!$this->dbforge->register_exists('errors', 'name', 'Categoria')){
            $this->db->query("INSERT INTO `errors` (`name`, `status`, `msg`, `icon`) VALUES ('Categoria', 1,'Reprovado por categoria inválida', 'fa-solid fa-sitemap icon-color')");
        }
        if (!$this->dbforge->register_exists('errors', 'name', 'Dimensões')){
            $this->db->query("INSERT INTO `errors` (`name`, `status`, `msg`, `icon`) VALUES ('Dimensões', 1,'Reprovado por dimensões inválidas', 'fa-solid fa-ruler-vertical icon-color')");
        }
        if (!$this->dbforge->register_exists('errors', 'name', 'Preço')){
            $this->db->query("INSERT INTO `errors` (`name`, `status`, `msg`, `icon`) VALUES ('Preço', 1,'Reprovado por preço inválido', 'fa-solid fa-dollar-sign icon-color')");
        }
        if (!$this->dbforge->register_exists('errors', 'name', 'Descrição')){
            $this->db->query("INSERT INTO `errors` (`name`, `status`, `msg`, `icon`) VALUES ('Descrição', 1,'Reprovado por descrição inválida', 'fa-solid fa-file-lines icon-color')");
        }
    }
    public function down()	{
        $this->db->query("DELETE FROM errors WHERE `name` = 'imagem'");
        $this->db->query("DELETE FROM errors WHERE `name` = 'Categoria'");
        $this->db->query("DELETE FROM errors WHERE `name` = 'Dimensões'");
        $this->db->query("DELETE FROM errors WHERE `name` = 'Preço'");
        $this->db->query("DELETE FROM errors WHERE `name` = 'Descrição'");
    }
};