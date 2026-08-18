<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage generated PDFs stored in the WordPress uploads directory.
 */
class Woo_Zo_Mygeniki_Lite_Pdf_Manager
{
    /**
     * Resolve the plugin slug used for the uploads subdirectory.
     */
    protected function get_storage_slug()
    {
        if (defined('Woo_Zo_Mygeniki_Lite_SLUG')) {
            return Woo_Zo_Mygeniki_Lite_SLUG;
        }

        return 'woo-zo-mygeniki-lite';
    }

    /**
     * Return the absolute storage directory for generated PDFs.
     */
    public function get_storage_dir()
    {
        $upload_dir = wp_upload_dir();

        return trailingslashit($upload_dir['basedir']) . $this->get_storage_slug() . '/';
    }

    /**
     * Return the public storage URL for generated PDFs.
     */
    public function get_storage_url()
    {
        $upload_dir = wp_upload_dir();

        return trailingslashit($upload_dir['baseurl']) . $this->get_storage_slug() . '/';
    }

    /**
     * Ensure the storage directory exists and contains a safety index file.
     */
    public function ensure_storage()
    {
        $dir = $this->get_storage_dir();
        if (!wp_mkdir_p($dir)) {
            return false;
        }

        $index = $dir . 'index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }

        return is_writable($dir);
    }

    /**
     * Save a PDF binary to the uploads directory and return its path and URL.
     */
    public function save_pdf($filename, $binary)
    {
        if (!$this->ensure_storage()) {
            return false;
        }

        $path = $this->get_storage_dir() . sanitize_file_name($filename);
        if (false === file_put_contents($path, $binary)) {
            return false;
        }

        return array(
            'path' => $path,
            'url'  => $this->get_storage_url() . basename($path),
        );
    }

    /**
     * Return the public URL for a stored PDF path.
     */
    public function get_file_url($path)
    {
        return $this->get_storage_url() . basename((string) $path);
    }

    /**
     * Delete all stored PDF files and return the number of deleted files.
     */
    public function clear_all()
    {
        if (!$this->ensure_storage()) {
            return 0;
        }

        $deleted = 0;
        foreach (glob($this->get_storage_dir() . '*.pdf') as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Count the generated PDF files currently stored by the plugin.
     */
    public function count_files()
    {
        if (!$this->ensure_storage()) {
            return 0;
        }

        return count(glob($this->get_storage_dir() . '*.pdf'));
    }

}
