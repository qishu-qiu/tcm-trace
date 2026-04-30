<?php

if (!function_exists('save_base64_image')) {
    function save_base64_image(string $base64Data, string $directory = 'images'): ?string
    {
        $matches = [];
        if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $base64Data, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($extension, $allowedExtensions)) {
            return null;
        }

        $imageData = base64_decode($matches[2]);
        if ($imageData === false) {
            return null;
        }

        $maxSize = 5 * 1024 * 1024;
        if (strlen($imageData) > $maxSize) {
            return null;
        }

        $filename = uniqid() . '.' . $extension;
        $uploadPath = WRITEPATH . 'uploads/' . $directory . '/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $filepath = $uploadPath . $filename;

        if (!file_put_contents($filepath, $imageData)) {
            return null;
        }

        return '/uploads/' . $directory . '/' . $filename;
    }
}

if (!function_exists('delete_uploaded_file')) {
    function delete_uploaded_file(string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $filepath = WRITEPATH . ltrim($relativePath, '/');
        
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
}
