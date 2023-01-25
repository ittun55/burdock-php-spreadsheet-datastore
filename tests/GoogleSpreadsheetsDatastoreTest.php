<?php //declare(strict_types=1);

use Burdock\GoogleSpreadsheetsDatastore\GoogleSpreadsheetsDatastore;
use PHPUnit\Framework\TestCase;

/**
 * @covers GoogleSpreadsheetsDatastore
 */
final class GoogleSpreadsheetsDatastoreTest extends TestCase
{
    private $ds;
    private $sheetsId = "1pWLkkFM3v6lQM2cdRvh33ICXL-LIvatlIkW_QdUgxo8";
    private $sheetName = "TestSheet";


    protected function setUp(): void
    {
        $credentials = __DIR__.'/../credentials.json';
        $this->ds = new GoogleSpreadsheetsDatastore($credentials);
    }

    public function testConstruct(): void
    {
        $this->assertNotNull($this->ds);
    }

    public function testGetSheetValues(): void
    {
        $rows = $this->ds->getSheetValues($this->sheetsId, $this->sheetName);
        $this->assertGreaterThan(0, count($rows));
    }

    public function testFindRow(): void
    {
        $row = $this->ds->findRow($this->sheetsId, $this->sheetName, 0, 'a500');
        $this->assertEquals('a500', $row[0]);
    }

    public function testAppendRow(): void
    {
        $row = ['a501',	'b501',	'b502',	'b503',	'b504',	'b505',	'b506',	'b507',	'b508',	'b509',	'b510',	'b511',	'b512',	'b513',	'b514',	'b515',	'b516'];
        $result = $this->ds->appendRow($this->sheetsId, $this->sheetName, $row);
        var_dump($result);
        $rows = $this->ds->getSheetValues($this->sheetsId, $this->sheetName);
        $lastIdx = count($rows) - 1;
        $this->assertEquals($row, $rows[$lastIdx]);
    }

    public function testUpdateRow(): void
    {
        $row = ['a300',	'b501',	'b502',	'b503',	'b504',	'b505',	'b506',	'b507',	'b508',	'b509',	'b510',	'b511',	'b512',	'b513',	'b514',	'b515',	'b516'];
        $result = $this->ds->updateRow($this->sheetsId, $this->sheetName, $cellRange, [$row]);
        $rows = $this->ds->getSheetValues($this->sheetsId, $this->sheetName);
        $this->assertEquals($row, $rows[$rowIdx]);
    }
}