<?php
/*
Plugin Name:  PHP render
Description: This plugin defines a shortcode to execute and edit PHP code from a file and displays documentation.
Version: 1.6
Author: Salah Eddine Dik
*/

// Enqueue DataTables and its dependencies
function custom_php_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), '1.11.5', true);
    wp_enqueue_script('datatables-bootstrap', 'https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js', array('datatables'), '1.11.5', true);

    wp_enqueue_style('datatables-bootstrap-css', 'https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css');
    
    // Enqueue custom CSS file
    wp_enqueue_style('custom-php-styles', plugins_url('custom-styles.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'custom_php_enqueue_scripts');

// Function to include and execute content from specified PHP file
function custom_php_shortcode($atts) {
    $atts = shortcode_atts(array(
        'name' => '', // Attribute to specify the PHP file name
    ), $atts);

    if (empty($atts['name'])) {
        return ''; // If name attribute is not provided, return an empty string
    }

    ob_start();
    include(plugin_dir_path(__FILE__) . 'phpfiles/' . $atts['name']);
    $template_content = ob_get_clean();

    // Execute PHP code
    ob_start();
    eval('?>' . $template_content);
    $executed_content = ob_get_clean();

    return $executed_content;
}

// Register shortcode
add_shortcode('render-php', 'custom_php_shortcode');

// Function to display the PHP code editor
function custom_php_editor_shortcode() {
    ob_start();
    include(plugin_dir_path(__FILE__) . 'phpfiles/php-editor.php');
    $editor_content = ob_get_clean();

    return $editor_content;
}

// Register shortcode for PHP editor
add_shortcode('php_editor', 'custom_php_editor_shortcode');

// Add menu to plugin settings
function custom_php_add_menu() {
    add_menu_page('Custom PHP Shortcode with Editor', 'PHP render', 'manage_options', 'custom_php_documentation', 'custom_php_documentation_page');
    add_submenu_page('custom_php_documentation', 'PHP Editor', 'All file php', 'manage_options', 'custom_php_editor', 'custom_php_editor_page');
    add_submenu_page('custom_php_documentation', 'Import/Export PHP Files', 'Import/Export PHP Files', 'manage_options', 'custom_php_import_export', 'custom_php_import_export_page');
}
add_action('admin_menu', 'custom_php_add_menu');

// Documentation page
function custom_php_documentation_page() {
    ?>
    <div class="wrap">
        <h2>PHP render documentation</h2>
        <h3>Usage</h3>
        <p>To use the PHP RENDER plugin, insert the following shortcode into your post or page content:</p>
        <pre>[render-php name="example.php"]</pre>
        <p>This will execute the PHP code defined in the example.php file and display its output.</p>
        <h3>Documentation</h3>
        <p>For more details and advanced usage, please refer to the README file included with the plugin.</p>
    </div>
    <?php
}

// Editor page
function custom_php_editor_page() {
    $saved_message = ''; // Initialize $saved_message

    // Load PHP code from file if it exists
    $file_path = plugin_dir_path(__FILE__) . 'phpfiles/php-content.php';
    if (file_exists($file_path)) {
        $textarea_content = esc_textarea(file_get_contents($file_path));
    } else {
        $textarea_content = ''; // Set default content if file does not exist
    }

    // Check if form is submitted and update PHP code
    if (isset($_POST['save_php_code'])) {
        $php_code = wp_unslash($_POST['php_code']);
        $filename = sanitize_file_name($_POST['filename']); // Get the filename from the form
        
        // Check if PHP code is empty
        if (empty($php_code)) {
            $saved_message = '<p style="color: red;">PHP file is empty!</p>';
        } else {
            // Save the PHP code to the file
            file_put_contents(plugin_dir_path(__FILE__) . 'phpfiles/' . $filename, $php_code);
            $saved_message = '<p style="color: green;">PHP code saved successfully for file: ' . $filename . '!</p>';
        }
        // Update textarea content after saving
        $textarea_content = esc_textarea($php_code);
    } elseif (isset($_POST['create_php_file'])) { // Check if form is submitted to create a new PHP file
        $new_php_filename = sanitize_file_name($_POST['new_php_filename']);
        $new_php_file_content = sprintf('<?php echo "hello from new %s php"; ?>', $new_php_filename); // Content for new PHP file
        file_put_contents(plugin_dir_path(__FILE__) . 'phpfiles/' . $new_php_filename . '.php', $new_php_file_content);
        $saved_message = '<p style="color: green;">New PHP file created successfully!</p>';
    } elseif (isset($_POST['delete_php_file'])) { // Check if form is submitted to delete a PHP file
        $file_to_delete = sanitize_file_name($_POST['file_to_delete']);
        unlink(plugin_dir_path(__FILE__) . 'phpfiles/' . $file_to_delete);
        $saved_message = '<p style="color: green;">PHP file deleted successfully!</p>';
    } elseif (isset($_POST['edit_php_file'])) { // Check if form is submitted to edit a PHP file
        $file_to_edit = sanitize_file_name($_POST['file_to_edit']);
        $file_content = file_get_contents(plugin_dir_path(__FILE__) . 'phpfiles/' . $file_to_edit);
        $textarea_content = esc_textarea($file_content);
    }
    ?>
    <div class="wrap">
        <h2>PHP Editor</h2>
        <div id="php-editor-section">
            <?php echo $saved_message; ?>
            <p>Edit the PHP code below:</p>
            <form method="post" action="">
                <input type="hidden" name="filename" value="<?php echo isset($_POST['file_to_edit']) ? $_POST['file_to_edit'] : ''; ?>">
                <textarea name="php_code" rows="10" cols="50"><?php echo $textarea_content; ?></textarea>
                <br>
                <input type="submit" name="save_php_code" class="button button-primary" value="Save PHP Code">
            </form>
        </div>

        <h3>Create New PHP File</h3>
        <form method="post" action="">
            <label for="new_php_filename">Enter new PHP filename:</label>
            <input type="text" name="new_php_filename" id="new_php_filename">
            <input type="submit" name="create_php_file" class="button button-primary" value="Create PHP File">
        </form>

        <h3>Existing PHP Files</h3>
        <table id="php_files_table" class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Shortcode</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $php_files = glob(plugin_dir_path(__FILE__) . 'phpfiles/*.php');
                foreach ($php_files as $file) {
                    $file = str_replace(plugin_dir_path(__FILE__) . 'phpfiles/', '', $file);
                    $shortcode = '[render-php name="' . $file . '"]';
                    echo '<tr>';
                    echo '<td>' . $file . '</td>';
                    echo '<td>' . $shortcode . '</td>';
                    echo '<td>
                            <form method="post" action="">
                                <input type="hidden" name="file_to_delete" value="' . $file . '">
                                <input type="submit" name="delete_php_file" class="button button-primary" value="Delete">
                                <input type="hidden" name="file_to_edit" value="' . $file . '">
                                <input type="submit" name="edit_php_file" class="button button-primary" value="Edit">
                            </form>
                          </td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var phpEditorSection = document.getElementById('php-editor-section');

            // Check if the PHP Editor section should be shown initially
            var editPhpFile = "<?php echo isset($_POST['edit_php_file']) ? $_POST['edit_php_file'] : ''; ?>";
            if (editPhpFile) {
                phpEditorSection.style.display = 'block';
            }

            // Hide the "Edit PHP Code" button when the user clicks "Edit" in the table
            var editPhpButtons = document.querySelectorAll('input[name="edit_php_file"]');
            editPhpButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    phpEditorSection.style.display = 'block';
                });
            });
        });
    </script>
<?php
}


// Import/Export PHP files page
function custom_php_import_export_page() {
    if (isset($_POST['import_php_files'])) {
        // Handle importing PHP files from JSON
        if ($_FILES['import_file']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
            $imported_data = file_get_contents($_FILES['import_file']['tmp_name']);
            $imported_files = json_decode($imported_data, true);
            if (is_array($imported_files)) {
                foreach ($imported_files as $filename => $content) {
                    file_put_contents(plugin_dir_path(__FILE__) . 'phpfiles/' . $filename, $content);
                }
                echo '<div class="notice notice-success"><p>PHP files imported successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Invalid JSON file format!</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Error uploading file!</p></div>';
        }
    }

    if (isset($_POST['export_php_files'])) {
        $php_files = glob(plugin_dir_path(__FILE__) . 'phpfiles/*.php');
        $export_data = array();
        foreach ($php_files as $file) {
            $filename = basename($file);
            $content = file_get_contents($file);
            $export_data[$filename] = $content;
        }

        $json_data = json_encode($export_data, JSON_PRETTY_PRINT);
        $current_date = date('Y-m-d');
        $json_file_name = 'php_files_export_' . $current_date . '.json';
        $json_file_path = plugin_dir_path(__FILE__) . $json_file_name;
        file_put_contents($json_file_path, $json_data); // Save JSON file

        // Offer the JSON file for download and then delete it
        echo '<div class="notice notice-success"><p>PHP files exported successfully!</p>';
        echo '<a href="' . admin_url('admin-ajax.php?action=download_json_file&json_file_path=' . urlencode($json_file_path)) . '" class="button button-primary" download>Download JSON</a>';
        echo '</div>'; // Close the success message container
    }
    ?>
    <div class="wrap">
        <h2>Import/Export PHP Files</h2>
        <h3>Import PHP Files</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="import_file" accept=".json">
            <input type="submit" name="import_php_files" class="button button-primary" value="Import">
        </form>

        <h3>Export PHP Files</h3>
        <form method="post">
            <input type="submit" name="export_php_files" class="button button-primary" value="Export">
        </form>
    </div>
    <?php
}

// Add AJAX action for serving and deleting the JSON file
add_action('wp_ajax_download_json_file', 'custom_php_download_json_file');
function custom_php_download_json_file() {
    if (isset($_GET['json_file_path'])) {
        $json_file_path = urldecode($_GET['json_file_path']);
        if (file_exists($json_file_path)) {
            // Serve the file for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . basename($json_file_path) . '"');
            readfile($json_file_path);

            // Delete the file after download
            unlink($json_file_path);
            exit;
        }
    }
    wp_die();
}

// Add hooks for import/export pages
add_action('admin_menu', 'custom_php_add_menu');
?>
