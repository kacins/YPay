<?php
namespace app\common\util;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Excel通用类
 */
class Excel
{
    /**
     * 从数据库导出数据到表格
     * 
     *    使用方式
     *    use app\common\util\Excel;
     *    $title = '管理信息表';
     *    $column = ['日期','账号','状态'];
     *    $setWidh = ['30','30','30'];
     *    $list = AdminAdmin::select()->toArray();
     *    $keys = ['create_time','username','status'];
     *    $filename = "管理表";
     *    Excel::go($title, $column, $setWidh, $list, $keys,$filename);
     * 
     * @param sring $title 首行标题内容
     * @param array $column        第二行列头标题
     * @param array $setWidth      第二行列头宽度
     * @param array $list          从数据库获取表格内容
     * @param array $keys          要获取的内容键名
     * @param string $filename     导出的文件名
     */
    public static function go(string $title, array $column, array $setWidth, array $list, array $keys, string $filename='')
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $count = count($column);
        // 合并首行单元格
        $worksheet->mergeCells(chr(65).'1:'.chr($count+64).'1');
        $styleArray = [
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,],
        ];
        // 设置首行单元格内容
        $worksheet->setTitle($title);
        $worksheet->setCellValueByColumnAndRow(1, 1, $title);
        // 设置单元格样式
        $worksheet->getStyle(chr(65).'1')->applyFromArray($styleArray)->getFont()->setSize(18);
        $worksheet->getStyle(chr(65).'2:'.chr($count+64).'2')->applyFromArray($styleArray)->getFont()->setSize(12);
        // 设置列头内容
        foreach ($column as $key => $value) $worksheet->setCellValueByColumnAndRow($key+1, 2, $value);
        // 设置列头格式
        foreach ($setWidth as $k => $v) $worksheet->getColumnDimension(chr($k+65))->setWidth(intval($v));
        // 从数据库获取表格内容
        $len = count($list);
        $j = 0;
        for ($i=0; $i < $len; $i++){
            $j = $i + 3; //从表格第3行开始
            foreach ($keys as $kk => $vv){
                $worksheet->setCellValueByColumnAndRow($kk+1, $j, $list[$i][$vv]);
            }
        }
        $total_jzInfo = $len + 2;
        $styleArrayBody = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '666666'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $filename = $filename.date('_YmdHis');
        // 添加所有边框/居中
        $worksheet->getStyle(chr(65).'1:'.chr($count+64).$total_jzInfo)->applyFromArray($styleArrayBody);
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition:attachment;filename={$filename}.xlsx");
        header('Cache-Control: max-age=0');//禁止缓存
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }
}