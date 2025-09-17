<?php
/**
 * 水印處理器
 * 處理圖片水印添加功能
 */
class WatermarkProcessor {
    private $fixedFontPath;
    private $floatingFontPath;
    
    public function __construct() {
        $this->fixedFontPath = __DIR__ . '/../fonts/SourceHanSansOLD-Normal-2.otf';
        $this->floatingFontPath = __DIR__ . '/../fonts/Heavy.ttf';
    }
    
    /**
     * 處理上傳的圖片，添加水印
     * @param string $originalPath 原始圖片路徑
     * @param string $originalFilename 原始文件名
     * @param string $username 當前用戶名
     * @return array 返回處理結果
     */
    public function processImage($originalPath, $originalFilename, $username) {
        try {
            // 創建目標路徑
            $originalDir = __DIR__ . '/../uploads/original/';
            $watermarkedDir = __DIR__ . '/../uploads/watermarked/';
            
            // 確保目錄存在
            if (!is_dir($originalDir)) {
                mkdir($originalDir, 0755, true);
            }
            if (!is_dir($watermarkedDir)) {
                mkdir($watermarkedDir, 0755, true);
            }
            
            $originalDestPath = $originalDir . $originalFilename;
            $watermarkedDestPath = $watermarkedDir . $originalFilename;
            
            // 複製原圖到original文件夾
            if (!copy($originalPath, $originalDestPath)) {
                throw new Exception('無法保存原圖');
            }
            
            // 創建水印圖片
            $this->createWatermarkedImage($originalPath, $watermarkedDestPath, $username);
            
            // 刪除臨時文件
            unlink($originalPath);
            
            return [
                'success' => true,
                'original_url' => '/uploads/original/' . $originalFilename,
                'watermarked_url' => '/uploads/watermarked/' . $originalFilename,
                'filename' => $originalFilename
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 創建帶水印的圖片
     */
    private function createWatermarkedImage($sourcePath, $destPath, $username) {
        // 獲取圖片信息
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new Exception('無法讀取圖片信息');
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $imageType = $imageInfo[2];
        
        // 根據圖片類型創建圖像資源
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($sourcePath);
                // 保持PNG透明度
                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($sourcePath);
                break;
            default:
                throw new Exception('不支持的圖片格式');
        }
        
        if ($image === false) {
            throw new Exception('無法創建圖像資源');
        }
        
        // 添加固定水印（底部版權條）
        $this->addFixedWatermark($image, $width, $height, $username);
        
        // 添加浮動水印
        $this->addFloatingWatermark($image, $width, $height);
        
        // 保存圖片
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $destPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $destPath);
                break;
            case IMAGETYPE_GIF:
                imagegif($image, $destPath);
                break;
        }
        
        imagedestroy($image);
    }
    
    /**
     * 添加固定水印（底部版權條）
     */
    private function addFixedWatermark($image, $width, $height, $username) {
        // 設置水印條的高度
        $barHeight = 60;
        $fontSize = 16;
        
        // 創建不透明黑色背景矩形
        $backgroundColor = imagecolorallocate($image, 0, 0, 0); // 純黑色，不透明
        imagefilledrectangle($image, 0, $height - $barHeight, $width, $height, $backgroundColor);
        
        // 設置文字顏色（白色）
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // 左側文字：LKYSS PT
        $leftText = 'LKYSS PT';
        $leftBox = imagettfbbox($fontSize, 0, $this->fixedFontPath, $leftText);
        $leftX = 20;
        $leftY = $height - ($barHeight - ($leftBox[1] - $leftBox[7])) / 2;
        
        // 右側文字：Image Copyright @用戶名
        $rightText = 'Image Copyright @' . $username;
        $rightBox = imagettfbbox($fontSize, 0, $this->fixedFontPath, $rightText);
        $rightX = $width - abs($rightBox[4] - $rightBox[0]) - 20;
        $rightY = $height - ($barHeight - ($rightBox[1] - $rightBox[7])) / 2;
        
        // 繪製文字
        imagettftext($image, $fontSize, 0, $leftX, $leftY, $textColor, $this->fixedFontPath, $leftText);
        imagettftext($image, $fontSize, 0, $rightX, $rightY, $textColor, $this->fixedFontPath, $rightText);
    }
    
    /**
     * 添加浮動水印（隨機位置，單個水印）
     */
    private function addFloatingWatermark($image, $width, $height) {
        $fontSize = 48;
        $watermarkText = 'LKYSS PT';
        
        // 創建半透明文字顏色（白色，50%透明度）
        $textColor = imagecolorallocatealpha($image, 255, 255, 255, 64); // 64 = 50%透明度
        
        // 計算文字尺寸
        $textBox = imagettfbbox($fontSize, 0, $this->floatingFontPath, $watermarkText);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[1] - $textBox[7]);
        
        // 計算可放置水印的區域（確保水印完全在圖片內）
        $margin = 20; // 邊距
        $maxX = $width - $textWidth - $margin;
        $maxY = $height - $margin;
        $minX = $margin;
        $minY = $textHeight + $margin;
        
        // 隨機生成水印位置
        $randomX = rand($minX, max($minX, $maxX));
        $randomY = rand($minY, max($minY, $maxY));
        
        // 隨機旋轉角度（-45度到45度之間）
        $angle = rand(-45, 45);
        
        // 繪製單個浮動水印
        imagettftext($image, $fontSize, $angle, $randomX, $randomY, $textColor, $this->floatingFontPath, $watermarkText);
    }
    
    /**
     * 檢查GD擴展和字體文件
     */
    public function checkRequirements() {
        $errors = [];
        
        if (!extension_loaded('gd')) {
            $errors[] = 'GD擴展未安裝';
        }
        
        if (!file_exists($this->fixedFontPath)) {
            $errors[] = '固定水印字體文件不存在: ' . $this->fixedFontPath;
        }
        
        if (!file_exists($this->floatingFontPath)) {
            $errors[] = '浮動水印字體文件不存在: ' . $this->floatingFontPath;
        }
        
        return empty($errors) ? true : $errors;
    }
}
?>