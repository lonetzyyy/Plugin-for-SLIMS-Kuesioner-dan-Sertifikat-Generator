<?php
/**
 * Plugin Name: Kuesioner dan sertifikat generator
 * Plugin URI: -
 * Description: Plugin untuk membuat Kuesioner dan sertifikat generator seminar/webinar
 * Version: 1.0.0
 * Author: Akbar Triagi
 * Author URI: -
 */
use SLiMS\Plugins;
$plugins = Plugins::getInstance();

$plugins->registerMenu('opac', 'kuesioner', __DIR__ . '/pages/kuesioner.inc.php');
$plugins->registerMenu('system', 'Kuesioner dan sertifikat', __DIR__ . '/pages/settings_kuesioner.inc.php');
$plugins->registerMenu('circulation', 'kuesioner dan sertifikat', __DIR__ . '/pages/laporan_kuesioner.inc.php');