<?php
namespace Esmi\Logger;

class Logger {

  private $loglevel = [
    0 => "ZERO",
    1 => "FATAL",
    2 => "ERROR",
    3 => "WARN",
    4 => "INFO",
    5 => "DEBUG",
    6 => "TRACE"
  ];
  private $debug = 0;

  function dlog( $level, $format, ...$v) {
    // force: $level < 0 :
    //echo "level: $level, this->debug: {$this->debug}\n";
    //var_dump($level);
    if ( ($level <= $this->debug && $this->debug ) || $level < 0) {

      $logLevelString = ($level >= 0 ? ( $level<=6 ? $this->loglevel[$level]: "UndefinedLevel") : "FORCE");
      $format = "[%s\\%s %s] - " . $format;
      $trace = debug_backtrace();

      // Get the class that is asking for who awoke it
      $class = $trace[1]['class'];
      $function  = debug_backtrace()[1]['function'];
      // print(call_user_func_array("sprintf", array_merge([$format, static::class, $function, $logLevelString], $v)));
      print(call_user_func_array("sprintf", array_merge([$format, $class, $function, $logLevelString], $v)));
    }
  }
  function getlevelName($level) {
    if ($level > count($this->loglevel) || !isset($this->loglevel[$level])) {
      $this->dlog(-1, "Unknow debug level(%d)\n", level);
      return "Unknow debug level(%d)\n";
    }
    return $this->loglevel[$level];
  }
  function setlevel($debug=0) {
    $this->dlog(-1, "set debug level(%s)\n", $this->loglevel[$debug]);
    $this->debug = $debug;
  }
  function level() {
    return $this->debug;
  }
}
