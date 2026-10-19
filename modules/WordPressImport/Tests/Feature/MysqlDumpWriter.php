<?php

/**
 * Writes a (fake) WordPress database the way mysqldump / phpMyAdmin export it, for loader tests.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Tests\Feature;

use Illuminate\Database\Connection;

class MysqlDumpWriter
{
    /** @param  'mysqldump'|'phpmyadmin'  $style */
    public static function dump(Connection $db, string $style = 'mysqldump'): string
    {
        $pdo = $db->getPdo();
        $out = $style === 'phpmyadmin'
            ? "-- phpMyAdmin SQL Dump\n-- version 5.2.1\n-- https://www.phpmyadmin.net/\n--\n-- Host: localhost\n\nSET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSTART TRANSACTION;\nSET time_zone = \"+00:00\";\n\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n/*!40101 SET NAMES utf8mb4 */;\n\n"
            : "-- MySQL dump 10.13  Distrib 8.0.36, for Linux (x86_64)\n--\n-- Host: localhost    Database: khaneh_wp\n-- ------------------------------------------------------\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n/*!40101 SET NAMES utf8mb4 */;\n/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n\n";

        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $columns = $pdo->query('PRAGMA table_info("'.$table.'")')->fetchAll(\PDO::FETCH_ASSOC);
            $defs = array_map(fn ($c) => '  `'.$c['name'].'` '.self::type((string) $c['type']).($c['notnull'] ? ' NOT NULL' : ' DEFAULT NULL'), $columns);
            $first = $columns[0]['name'];

            $out .= "--\n-- Table structure for table `{$table}`\n--\n\n";
            if ($style === 'mysqldump') {
                $out .= "DROP TABLE IF EXISTS `{$table}`;\n/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
                $defs[] = '  PRIMARY KEY (`'.$first.'`)';
                $defs[] = '  KEY `'.$first.'_x` (`'.$first.'`(191))';
            }
            $out .= "CREATE TABLE `{$table}` (\n".implode(",\n", $defs)."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='wp (test), table';\n\n";

            $rows = $pdo->query('SELECT * FROM "'.$table.'"')->fetchAll(\PDO::FETCH_NUM);
            if ($rows) {
                $tuples = array_map(fn ($row) => '('.implode(',', array_map(self::value(...), $row)).')', $rows);
                if ($style === 'mysqldump') {
                    $out .= "LOCK TABLES `{$table}` WRITE;\n/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n";
                    foreach (array_chunk($tuples, 3) as $chunk) {
                        $out .= "INSERT INTO `{$table}` VALUES ".implode(',', $chunk).";\n";
                    }
                    $out .= "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\nUNLOCK TABLES;\n\n";
                } else {
                    $cols = implode(', ', array_map(fn ($c) => '`'.$c['name'].'`', $columns));
                    $out .= "--\n-- Dumping data for table `{$table}`\n--\n\nINSERT INTO `{$table}` ({$cols}) VALUES\n".implode(",\n", $tuples).";\n\n";
                }
            }
            if ($style === 'phpmyadmin') {
                $out .= "ALTER TABLE `{$table}`\n  ADD PRIMARY KEY (`{$first}`),\n  ADD KEY `x` (`{$first}`);\n\n";
            }
        }

        // noise every real dump has: a log table, a trigger, a view
        $out .= "CREATE TABLE `wp_actionscheduler_logs` (\n  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n  `message` text NOT NULL,\n  PRIMARY KEY (`log_id`)\n) ENGINE=InnoDB;\n"
            ."INSERT INTO `wp_actionscheduler_logs` VALUES (1,'action created; it\\'s fine'),(2,'done');\n\n"
            ."DELIMITER ;;\n/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `t1` BEFORE INSERT ON `wp_posts` FOR EACH ROW BEGIN\n  SET NEW.post_title = 'x;y';\nEND */;;\nDELIMITER ;\n\n"
            ."/*!50001 CREATE VIEW `v_posts` AS select 1 AS `ID` */;\n"
            .($style === 'phpmyadmin' ? "COMMIT;\n" : "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n\n-- Dump completed on 2026-10-18 21:00:00\n");

        return $out;
    }

    private static function type(string $sqlite): string
    {
        return match (true) {
            str_contains(strtoupper($sqlite), 'INT') => 'bigint(20) unsigned',
            str_contains(strtoupper($sqlite), 'NUMERIC') || str_contains(strtoupper($sqlite), 'REAL') || str_contains(strtoupper($sqlite), 'DEC') => 'decimal(26,8)',
            str_contains(strtoupper($sqlite), 'DATE') => 'datetime',
            default => 'longtext',
        };
    }

    private static function value(mixed $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }

        return "'".strtr((string) $v, ['\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\n" => '\\n', "\r" => '\\r', "\0" => '\\0', "\x1a" => '\\Z'])."'";
    }
}
