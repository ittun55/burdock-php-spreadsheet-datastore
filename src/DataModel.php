<?php //declare(strict_types=1);
namespace Burdock\GoogleSpreadsheetsDatastore;

use PhpParser\Lexer\TokenEmulator\ExplicitOctalEmulator;

class DataModel
{
    private $sheetsId  = null;
    private $sheetName = null;
    private $datastore = null;
    private $fields    = [
        'id' => [
            'type' => 'string',
            'opts' => ['PK','AI','NN']
        ],
        'created_at' => [
            'type' => 'datetime',
            'opts' => ['NN']
        ],
        'created_by' => [
            'type' => 'string',
            'opts' => ['NN']
        ],
        'updated_at' => [
            'type' => 'datetime',
            'opts' => ['NN']
        ],
        'updated_by' => [
            'type' => 'string',
            'opts' => ['NN']
        ],
        'deleted_at' => [
            'type' => 'datetime',
            'opts' => []
        ],
        'deleted_by' => [
            'type' => 'string',
            'opts' => []
        ]
    ];
    private $_data = [];

    public function __construct(array $schema, string $credentials, string $sheetsId, string $sheetName)
    {
        $this->datastore = new GoogleSpreadsheetsDatastore($credentials);
        $this->sheetsId  = $sheetsId;
        $this->sheetName = $sheetName;
    }

    public function loadSchema()
    {

    }

    public function findAll(): array
    {
        return [];
    }

    public function paginate()
    {

    }

    public function insert($user='app'): array
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->_data['id'] = $this->getUniqId();
        $this->_data['created_at'] = $timestamp;
        $this->_data['created_by'] = $user;
        $this->_data['updated_at'] = $timestamp;
        $this->_data['updated_by'] = $user;
        $row = array_values($this->_data);
        $this->datastore->appendRows($this->sheetsId, $this->sheetName, [$row]);
        return $this->_data;
    }

    public function getUniqId(): string
    {
        list($sec, $msec) = explode('.', microtime(true));
        return dechex($sec) . "." . dechex($msec) . "." . bin2hex(random_bytes(1));
    }

    public function update($user='app'): array
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->_data['updated_at'] = $timestamp;
        $this->_data['updated_by'] = $user;
        $row = array_values($this->_data);

        $rows = $this->datastore->getSheetValues($this->sheetsId, $this->sheetName);
        $pKeyColIdx = 0;
        $pKeyVal = $row[$pKeyColIdx];
        $rowIdx = array_search($pKeyVal, array_column($rows, $pKeyColIdx));
        $rowNum = $rowIdx + 1;
        $colLastNum = count($row) - 1;
        $cellRange = "R{$rowNum}C1:R{$rowNum}C{$colLastNum}";

        $this->datastore->updateRows($this->sheetsId, $this->sheetName, $cellRange, [$row]);

        return $this->_data;
    }

    public function delete($user='app', $hard=false): array
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->_data['updated_at'] = $timestamp;
        $this->_data['updated_by'] = $user;
        return $this->_data;
    }
}