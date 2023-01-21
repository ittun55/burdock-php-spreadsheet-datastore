<?php
require_once __DIR__ . '/../vendor/autoload.php';
 
use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
 
class GoogleSpreadsheetsAdapter implements LoggerAwareInterface
{
    use LoggerAwareTrait;
 
    /**
     * Returns the PSR-3 logger object.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }
 
    public $client;
    public $cacheDir;
 
    /**
     * サービスアカウントによる認証とし $authJson は認証設定内容
     */
    public function __construct(string $authJson, string $cacheDir='/tmp/')
    {
        $this->client = new Client();
        $this->client->setAuthConfig($authJson);
        $this->client->addScope(Drive::DRIVE);
        $this->client->addScope(Sheets::SPREADSHEETS);
        $this->client->setApplicationName("GoogleSpreadsheetsAdapter"); // 適当な名前でOK
        $this->sheets = new Sheets($client);
        $this->cacheDir = $cacheDir;
    }
 
    /**
     * 対象シートの全データを取得（セル範囲を指定すれば範囲データの取得も可）
     * https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets.values/get
     */
    public function getSheetValues(string $sheetsId, string $sheetName, string $cellRange='')
    {
        // データを取得する範囲 シート名!A1:C5 またはで指定.
        // セル範囲を指定しなければ、シート全体のデータを取得する
        $range = (cell_range) ? "${sheetName}!${cellRange}" : $sheetName;
        $res   = $this->sheets->spreadsheets_values->get($sheetsId, $range);
        return $res->getValues();
    }
 
    /**
     * 行データを取得
     */
    public function findItem(string $sheetsId, string $sheetName, $keyColIdx, $keyVal)
    {
        $rows = $this->getSheetValues($sheetsId, $sheetName);
        $rowIdx = array_search($key_val, array_column($rows, $keyColIdx));
        return $rows[$rowIdx];
    }
 
    /**
     * 行データを更新
     */
    public function updateItem(string $sheetsId, string $sheetName, string $cellRange, array $data)
    {
        $range  = "${sheetName}!${cellRange}";
        $values = new ValueRange(['values' => $data]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $result = $this->sheets->spreadsheets_values->update(
            $sheets_id,
            $range,
            $values,
            $params
        );
        $this->saveCache($sheetsId, $sheetName);
        return $result;
    }
 
    /**
     * 行データを最終行に追加
     * $itemData の形式は 1 行でも配列の配列が必須
     */
    public function appendItem(string $sheetsId, string $sheetName, array $itemData)
    {
        $range  = "${sheetName}";
        $values = new ValueRange(['values' => [$itemData]]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $result = $this->sheets->spreadsheets_values->append(
            $sheets_id,
            $range,
            $values,
            $params
        );
        $this->saveCache($sheetsId, $sheetName);
        return $result;
    }
 
    /**
     * プライマリキー相当のカラムインデックスを取得する
     * @params array $col_defs ['id', 'name', 'status', ... ]
     * @params string $key_col 'id'
     * @returns int
     */
    public static function getKeyColIdx(array $col_defs, string $key_col): int
    {
        $idx = array_search($key_col, $col_defs);
        if (false === $idx) {
            throw new Exception('could not find key column from COL_DEFS');
        }
        return $idx;
    }
 
    public function saveCache(string $sheetsId, string $sheetName)
    {
        ;
    }
 
    public function readCache(string $sheetsId, string $sheetName)
    {
        ;
    }
}