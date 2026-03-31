<?php
namespace App\Helpers;

/**
 * IDCardHelper — Generates digital ID cards for students
 */
class IDCardHelper
{
    /**
     * Generate ID Card PNG
     * 
     * @param array $institute { name, logo_path }
     * @param array $student { full_name, roll_no, email, phone, permanent_address, photo_url }
     * @return string|bool Binary PNG data or false on failure
     */
    public static function generate($institute, $student)
    {
        // Dimensions
        $width = 600;
        $height = 400;
        
        $img = imagecreatetruecolor($width, $height);
        
        // Colors
        $white = imagecolorallocate($img, 255, 255, 255);
        $primary = imagecolorallocate($img, 0, 158, 126); // #009E7E
        $dark = imagecolorallocate($img, 30, 41, 59);    // #1E293B
        $light = imagecolorallocate($img, 226, 232, 240);  // #E2E8F0
        $textBody = imagecolorallocate($img, 71, 85, 105); // #475569
        
        imagefill($img, 0, 0, $white);
        
        // Header Background
        imagefilledrectangle($img, 0, 0, $width, 100, $primary);
        
        // Add Institute Name
        $fontPath = __DIR__ . '/../../assets/fonts/PlusJakartaSans-ExtraBold.ttf';
        if (!file_exists($fontPath)) {
            // Fallback to internal font if TTF not found
            imagestring($img, 5, 20, 35, strtoupper($institute['name'] ?? 'INSTITUTE NAME'), $white);
        } else {
            imagettftext($img, 22, 0, 30, 60, $white, $fontPath, strtoupper($institute['name'] ?? 'INSTITUTE NAME'));
        }
        
        // Draw Profile Area
        imagefilledrectangle($img, 30, 120, 180, 270, $light);
        
        // Load Student Photo if available
        if (!empty($student['photo_url'])) {
            $photoPath = str_replace(APP_URL, __DIR__ . '/../../', $student['photo_url']);
            if (file_exists($photoPath)) {
                $ext = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
                $studentPhoto = null;
                if ($ext === 'jpg' || $ext === 'jpeg') $studentPhoto = imagecreatefromjpeg($photoPath);
                elseif ($ext === 'png') $studentPhoto = imagecreatefrompng($photoPath);
                
                if ($studentPhoto) {
                    imagecopyresampled($img, $studentPhoto, 30, 120, 0, 0, 150, 150, imagesx($studentPhoto), imagesy($studentPhoto));
                    imagedestroy($studentPhoto);
                }
            }
        }
        
        // Student Details
        $left = 210;
        if (!file_exists($fontPath)) {
            imagestring($img, 5, $left, 130, strtoupper($student['full_name'] ?? 'STUDENT NAME'), $dark);
            imagestring($img, 3, $left, 160, "Roll: " . ($student['roll_no'] ?? 'N/A'), $textBody);
            imagestring($img, 3, $left, 190, "Contact: " . ($student['phone'] ?? 'N/A'), $textBody);
            imagestring($img, 3, $left, 210, "Email: " . ($student['email'] ?? 'N/A'), $textBody);
        } else {
            imagettftext($img, 18, 0, $left, 150, $dark, $fontPath, strtoupper($student['full_name'] ?? 'STUDENT NAME'));
            
            $regFont = __DIR__ . '/../../assets/fonts/PlusJakartaSans-Medium.ttf';
            if (!file_exists($regFont)) $regFont = $fontPath;
            
            imagettftext($img, 12, 0, $left, 180, $textBody, $regFont, "Roll No: " . ($student['roll_no'] ?? 'N/A'));
            imagettftext($img, 12, 0, $left, 210, $textBody, $regFont, "Contact: " . ($student['phone'] ?? 'N/A'));
            imagettftext($img, 12, 0, $left, 235, $textBody, $regFont, "Email:   " . ($student['email'] ?? 'N/A'));
            
            $addr = $student['permanent_address'] ?? '';
            if (is_string($addr) && strpos($addr, '{') === 0) {
                $a = json_decode($addr, true);
                if ($a) $addr = ($a['local'] ?? '') . ", " . ($a['district'] ?? '');
            }
            imagettftext($img, 12, 0, $left, 260, $textBody, $regFont, "Address: " . (is_string($addr) ? $addr : 'N/A'));
        }
        
        // Footer Border
        imagefilledrectangle($img, 0, 380, $width, $height, $primary);
        
        // Capture output
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);
        
        return $data;
    }
}
