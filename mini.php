<?php
$dir = dirname(__FILE__);
require($dir . '/' . 'cssmin.php');

/**
 * Формат файла:
 * * полное_имя_файла unix_timestamp
 * unix_timestamp может быть не задан; в таком случае файл считается новым.
 * Имя файла должно оканчиваться на .css или .js.
 */
define('MINIFIER_FILE_LIST', 'files-minifier.lst');
/** Окончание имени выходного файла для CSS */
define('CSS_OUTPUT_POSTFIX', '.min.css');
/** Окончание имени выходного файла для JS */
define('JS_OUTPUT_POSTFIX', '.min.js');


print 'Start processing on ' . date("d.m.Y H:i:s") . "\n";
$file_list_fullpath = $dir . '/' . MINIFIER_FILE_LIST;
if (false === $file_list =  file($file_list_fullpath)) {
  print 'Can not open: ' . $file_list_fullpath . "\n";
}

foreach ($file_list as $file_list_idx => $file_entry) {
  $file_entry_data = explode(' ', rtrim($file_entry));
  if (empty($file_entry_data[0])) {
    continue;
  }
  $file_name = $file_entry_data[0];

  // Дата предыдущей обработки - её может и не быть
  $file_processed_time = empty($file_entry_data[1]) ? 0 : (int) $file_entry_data[1];
  if (!file_exists($file_name) || (false === $file_mtime = filemtime($file_name))) {
    continue;
  }

  // Файл был обновлён после обхода минификатором
  if ($file_mtime > $file_processed_time) {
    $status_ok = false;
    if (preg_match('~\.css$~i', $file_name)) {
      $status_ok = generateMinifiedCss($file_name);
    }
    elseif (preg_match('~\.js$~i', $file_name)) {
      $status_ok = generateMinifiedJs($file_name);
    }

    if ($status_ok) {
      $file_list[$file_list_idx] = $file_name . ' ' . time() . "\n";
    }
    print 'Processed [' . ($status_ok ? 'OK' : 'ERROR') . ']: ' . $file_name . "\n";
  }
  else {
    print 'Skipping: ' . $file_name . "\n";
  }
}

// Что-то было обработано
if (!empty($status_ok)) {
  file_put_contents($file_list_fullpath, implode('', $file_list));
}

print 'End of processing at ' . date("d.m.Y H:i:s") . "\n---\n\n";


/**
 * Генерирует минифицированный CSS-файл.<br />
 * Результат: созданный файл в исходной папке с добавленным постфиксом OUTPUT_CSS_POSTFIX.
 *
 * @see OUTPUT_CSS_POSTFIX
 * @param string $filename Имя исходного файла
 * @return boolean Успешность операции
 */
function generateMinifiedCss($filename)
{
  $processed_ok = true;

  $output_filename = getOutputFilename($filename, CSS_OUTPUT_POSTFIX);
  $source_code = file_get_contents($filename);
  if (false !== $source_code) {
    $processed_code = CssMin::minify(file_get_contents($filename));

    // Если ошибка в обработке минификатором, копируем исходный файл
    if (0 == strlen($processed_code)) {
      $processed_ok = false;
    }
    else {
      file_put_contents($output_filename, $processed_code);
      setSameFileRestrictions($filename, $output_filename);
    }
  }
  else {
    $processed_ok = false;
  }

  if (!$processed_ok) {
    copySourceToResultFile($filename, $output_filename);

    return false;
  }

  return true;
}

function generateMinifiedJs($filename)
{
  $processed_ok = true;

  $output_filename = getOutputFilename($filename, JS_OUTPUT_POSTFIX);
  $source_code = file_get_contents($filename);
  if (false !== $source_code) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL,"http://closure-compiler.appspot.com/compile");
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'js_code'           => $source_code,
      'output_format'     => 'text',
      'output_info'       => 'compiled_code',
      'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
    )));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $processed_code = curl_exec($curl);

    if (false !== $processed_code) {
      file_put_contents($output_filename, $processed_code);
      setSameFileRestrictions($filename, $output_filename);
    }
    else {
      $processed_ok = false;
    }
    curl_close ($curl);
  }
  else {
    $processed_ok = false;
  }

  if (!$processed_ok) {
    copySourceToResultFile($filename, $output_filename);

    return false;
  }

  return true;
}

/*
 * Сгенерировать имя выходного файла
 *
 * @param string $filename Имя файла
 * @param string $postfix Постфикс
 * @return string
 */
function getOutputFilename($filename, $postfix)
{
  $parts = explode('.', $filename);
  array_pop($parts);
  return implode('.', $parts) . $postfix;
}

/**
 * Записать на место нового файла старый, сохраняя права доступа
 *
 * @param $src_filename
 * @param $output_filename
 */
function copySourceToResultFile($src_filename, $output_filename)
{
  unlink($output_filename);
  copy($src_filename, $output_filename);
  $src_stat = stat($src_filename);
  setSameFileRestrictions($src_filename, $output_filename);
}

/**
 * Установить права (флаги доступа и владельца) исходного файла на выходной
 *
 * @param $src_filename
 * @param $output_filename
 */
function setSameFileRestrictions($src_filename, $output_filename)
{
  $src_stat = stat($src_filename);
  chmod($output_filename, $src_stat['mode']);
  chown($output_filename, $src_stat['uid']);
  chgrp($output_filename, $src_stat['gid']);
}
