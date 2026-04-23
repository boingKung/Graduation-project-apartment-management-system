<?php

namespace App\Traits;
use Carbon\Carbon;

trait FormatHelper
{
    // เปลี่ยนจาก private เป็น public หรือ protected เพื่อให้ Controller นำไปใช้ได้
    // แปลงเป็นวันที่ไทย
    protected function toThaiDate($date, $showDay = true, $shortMonth = false)
    {
        if (!$date) return '-';

        $carbon = Carbon::parse($date)->locale('th');
        $thaiYear = $carbon->year + 543; // แปลงเป็น พ.ศ.

        // 💡 กำหนด Format: 'MMM' คือเดือนย่อ (ม.ค.), 'MMMM' คือเดือนเต็ม (มกราคม)
        $monthFormat = $shortMonth ? 'MMM' : 'MMMM';

        if ($showDay) {
            // ผลลัพธ์ตัวอย่าง: 13 ม.ค. 2569
            return $carbon->isoFormat("D $monthFormat") . ' ' . $thaiYear;
        } else {
            // ผลลัพธ์ตัวอย่าง: ม.ค. 2569
            return $carbon->isoFormat($monthFormat) . ' ' . $thaiYear;
        }
    }
        /**
     * ฟังก์ชันสำหรับแปลงตัวเลขเป็นตัวอักษรภาษาไทย (Baht Text)
     */
    protected function bahtText($number)
    {
        $number = number_format($number, 2, '.', '');
        $number_parts = explode('.', $number);
        $baht = $number_parts[0];
        $satang = $number_parts[1];

        $result = $this->convertText($baht) . 'บาท';

        if ($satang == '00') {
            $result .= 'ถ้วน';
        } else {
            $result .= $this->convertText($satang) . 'สตางค์';
        }

        return $result;
    }

    protected function convertText($number)
    {
        $txtnum_th = array('ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า');
        $txtunit_th = array('', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน');
        $result = "";
        $len = strlen($number);

        for ($i = 0; $i < $len; $i++) {
            $digit = substr($number, $i, 1);
            if ($digit != '0') {
                if ($i == ($len - 1) && $digit == '1' && $len > 1) {
                    $result .= 'เอ็ด';
                } elseif ($i == ($len - 2) && $digit == '2') {
                    $result .= 'ยี่สิบ';
                } elseif ($i == ($len - 2) && $digit == '1') {
                    $result .= 'สิบ';
                } else {
                    $result .= $txtnum_th[$digit] . $txtunit_th[$len - $i - 1];
                }
            }
        }
        return $result;
    }
}