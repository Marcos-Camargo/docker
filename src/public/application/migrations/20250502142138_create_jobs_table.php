<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("
			CREATE TABLE `jobs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default',
  `payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `payload_hash` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `class_hash` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `reserved_timeout_seconds` int(11) NULL DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NULL DEFAULT NULL,
  `retry_delay_seconds` int(11) NULL DEFAULT NULL,
  `failed_at` datetime(0) NULL DEFAULT NULL,
  `failed_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `reserved_at` datetime(0) NULL DEFAULT NULL,
  `available_at` datetime(0) NOT NULL,
  `created_at` datetime(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `queue_index`(`queue`) USING BTREE,
  INDEX `available_at_index`(`available_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;");
	}

	public function down()	{
	}
};