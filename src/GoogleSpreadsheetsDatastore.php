<?php //declare(strict_types=1);
namespace Burdock\GoogleSpreadsheetsDatastore;

use Google\Client;
use Google\Exception;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\UpdateValuesResponse;
use Google\Service\Sheets\ValueRange;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
 
class GoogleSpreadsheetsDatastore implements LoggerAwareInterface
{
    use LoggerAwareTrait;
 
    /**
     * Returns the PSR-3 logger object.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }
 
    public $client;
    public $sheets;
 
    /**
     * サービスアカウントによる認証とし $authJson は認証設定内容
     */
    public function __construct(string $authJson)
    {
        $this->client = new Client();
        $this->client->setAuthConfig($authJson);
        $this->client->addScope(Drive::DRIVE);
        $this->client->addScope(Sheets::SPREADSHEETS);
        $this->client->setApplicationName("GoogleSpreadsheetsAdapter"); // 適当な名前でOK
        $this->sheets = new Sheets($this->client);
    }
 
    /**
     * 対象シートの全データを取得（セル範囲を指定すれば範囲データの取得も可）
     * https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets.values/get
     */
    public function getSheetValues(string $sheetsId, string $sheetName, string $cellRange='')
    {
        // データを取得する範囲 シート名!A1:C5 またはで指定.
        // セル範囲を指定しなければ、シート全体のデータを取得する
        $range = ($cellRange) ? "{$sheetName}!{$cellRange}" : $sheetName;
        $res   = $this->sheets->spreadsheets_values->get($sheetsId, $range);
        return $res->getValues();
    }
 
    /**
     * 行データを取得
     */
    public function findRow(string $sheetsId, string $sheetName, int $pKeyColIdx, $pKey): array
    {
        $rows = $this->getSheetValues($sheetsId, $sheetName);
        $rowIdx = array_search($pKey, array_column($rows, $pKeyColIdx));
        return $rows[$rowIdx];
    }
 
    /**
     * 行データを最終行に追加
     * $row の形式は 1 行でも配列の配列が必須
     */
    public function appendRows(string $sheetsId, string $sheetName, array $rows)
    {
        $range  = "{$sheetName}";
        $values = new ValueRange(['values' => $rows]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        return $this->sheets->spreadsheets_values->append($sheetsId, $range, $values, $params);
    }
 
    /**
     * 最初の行をプライマリキーとし、行データを更新（１行のみ）
     * @param string $sheetsId 
     * @param string $sheetName 
     * @param array $row 
     * @return UpdateValuesResponse 
     * @throws Exception 
     */
    public function updateRows(string $sheetsId, string $sheetName, string $cellRange, array $rows)
    {
        $range  = "{$sheetName}!{$cellRange}";
        $values = new ValueRange(['values' => $rows]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        return  $this->sheets->spreadsheets_values->update($sheetsId, $range, $values, $params);
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
            throw new \Exception('could not find key column from COL_DEFS');
        }
        return $idx;
    }

    /**
     * R1C1 の数値から A1 のアルファベット文字列に変換
     * @param int R1C1 の数値
     * @return string A1 のアルファベット文字列
     */
    public function getA1Str(int $input): string
    {
        $ret = '';
        do {
            $tmp   = gmp_div_qr($input, 26);
            $input = gmp_intval($tmp[0]);
            $amari = gmp_intval($tmp[1]);
            // 割り切れる場合は0ではなく26として扱う
            if($amari === 0){
                $input--;
                $amari = 26;
            }
            $ret .= chr($amari+64);
        } while($input);

        return strrev($ret);
    }

    /**
     * A1 のアルファベット文字列から R1C1 の数値に変換
     * @param String A1 のアルファベット文字列
     * @return int R1C1 の数値
     */
    public function getR1C1Int(string $input): int
    {
        $digit = strlen($input)-1;
        $ret = 0;
        for($i=0;$i<=$digit;$i++){
            $ret += (ord($input[$digit-$i])-64) * (26**$i);
        }
        return $ret;
    }
}