<?php
namespace Esmi\Logger;
use Esmi\DS\RecordQueue;

class EventLogger extends RecordQueue {

  private $history = null;

  function __construct( $history = null, $maxRecord = 5, $recordSize=1024, $key=0xff1 ) {
    parent::__construct($key,$maxRecord, $recordSize) ;

    if ($history != null)
      $this->history = $history;
  }
  function setHistory($history) {
    $this->history = $history;
  }
  function readall($reverse = true) {
    return parent::readall($reverse);
  }
  function append($d) {

    // $rec = [
    //   'datatime' => $datatime,
    //   'sourc' => $device,
    //   'message' => $message,
    //   'data' => $factor != null ? $factor['data'] : null,
    //   'acount' => $factor != null ? $factor['account'] : null
    // ];
    $datetime = $d['datatime'] == null ? date("m/d H:i") : (new \DateTime($d['datatime']))->format("m/d H:i");

    parent::append(['message' => $datetime . "-" . $d['message']]);

    if ( $this->history ) {
      // append event message to EventHistory;
      $datatime = date("Y-m-d H:i:s");
      $d['datatime'] = $d['datatime'] == null ? $datatime : $d['datatime'] ;
      $this->history->append($d);
    }
  }
  function readQueue($r = 5 ) {
    $data = $this->readAll();
    if ($r >= count($data)) {
      return $data;
    }
    else {
      return array_slice($data,  -$r);
    }
  }
  function logItem($device, $it, $factor = null){
    $message = "";
    // $datetime = date("Y-m-d H:i:s");
    //$datetime = date("m/d H:i");
    // echo "varlueAlertPM25: {$factor['valueAlertPM25']}\r\n";
    switch($device) {
      case 'alert': {
        if ( $it['alert'] == true ) {
          $message = "{$datetime}-{$it['message']}";
          break;
        }
      }
      case 'meter': {

        if ($it['conn_status'] != 0) {
          // echo "upperCapacity: {$factor['upperCapacity']}, contractCapacity: {$factor['contractCapacity']}\r\n";
          if ($it['demand_15min'] > $factor['upperCapacity']) {
            $message =  "15分鐘需量({$it['demand_15min']})-高於需量上限({$factor['upperCapacity']})";
          }
          if ($it['demand_15min'] > $factor['contractCapacity']) {
            $message =  $message . ($message != "" ? "-" : "") .  "高於契約容量({$factor['contractCapacity']})";
          }
          if ($it['conn_quality'] <= $it['brokenQuality'] ) {
            $message = $message .  ($message != "" ? "-" : "" ). "斷線({$it['conn_quality']} <= ${it['brokenQuality']} )";
          }
          if ($it['conn_quality'] <= $it['goodQuality']) {
            $message = $message .  ($message != "" ? "-" : "") .  "連線不良({$it['conn_quality']} <= ${it['goodQuality']})";
          }
        }
        else {
          $message =  "需量表--離線";
        }
        break;
      }
      case 'sensor': {
        if ($it['sensor_status'] != 0) {
          if ($it['temperature'] > $factor['maxTemperature']) {
            $message =  "溫度({$it['temperature']})-高於上限({$factor['maxTemperature']})";
          }
        }
        else {
          $message = "溫度模組--離線"; //sensor
        }
        break;
      }
      case 'air': {
        if ($it['air_status'] != 0) {

          if ($it['pm25'] > $factor['valueAlertPM25']) {
            $message =  "pm25({$it['pm25']})-高於上限({$factor['valueAlertPM25']})";
          }
        }
        else {
          $message =  "環境模組--離線"; //air
        }
        break;
      }
      // for factor: 參數修改
      case 'factor': {
        $message = "{$it['message']}";
        break;
      }

      // for dgroup:
      case 'unload': {
        $message = "群組{$it['gid']}自動卸載,15min({$it['demand_15min']}),1min({$it['demand_1min']})";
        break;
      }
      case 'reload': {
        $message = "群組{$it['gid']}復歸,15min({$it['demand_15min']}),1min({$it['demand_1min']})";
        break;
      }

    }
    if ($message != "" && $message != "-") {
      //echo "message: $message\r\n";
      // `datatime` DATETIME NULL DEFAULT NULL,
    	// `souce` VARCHAR(20) NULL DEFAULT NULL COMMENT '事件來源',
    	// `message` VARCHAR(50) NULL DEFAULT NULL COMMENT '事件名稱, 事件訊息',
    	// `data` VARCHAR(50) NULL DEFAULT NULL COMMENT '事件資料, 變更項目及變更後內容',
    	// `account` INT(11) NULL DEFAULT NULL COMMENT '變更者'
      $rec = [
        'datatime' => isset($it['datatime']) ? $it['datatime'] : null,
        'source' => $device,
        'message' => $message,
        'data' => isset($it['data']) ? $it['data'] : null,
        'acount' => isset($it['account'])? $it['account'] : null
      ];
      $this->append($rec);
    }
    else {
      // message == "" do nothing.
    }
  }
}
