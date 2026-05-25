<?php
$conn = new mysqli("localhost", "root", "", "db_1");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

/* SAVE / UPDATE TEMPLATE ASSIGN */
if (isset($_POST['save'])) {
    $store_id = $_POST['store_id'];
    $template_id = $_POST['template_id'];
    $font_name = isset($_POST['font_name']) ? $_POST['font_name'] : '';
    
    if ($font_name) {
        $conn->query("UPDATE templates SET font_name = '$font_name' WHERE id = '$template_id'");
    }

    /* Check already exists */
    $check = $conn->query("
        SELECT id 
        FROM store_template 
        WHERE store_id = '$store_id'
    ");

    if ($check->num_rows > 0) {
        /* UPDATE */
        $sql = "
            UPDATE store_template 
            SET template_id = '$template_id', font_name = '$font_name'
            WHERE store_id = '$store_id'
        ";

        if ($conn->query($sql)) {
            $message = "Template updated successfully!";
        } else {
            $message = "Update failed!";
        }

    } else {
        /* INSERT FIRST TIME */
        $sql = "
            INSERT INTO store_template (store_id, template_id, font_name)
            VALUES ('$store_id', '$template_id', '$font_name')
        ";

        if ($conn->query($sql)) {
            $message = "Template assigned successfully!";
        } else {
            $message = "Insert failed!";
        }
    }
}

/* FETCH STORES */
$stores = $conn->query("
    SELECT * FROM stores
    ORDER BY store_name ASC
");

/* FETCH TEMPLATES */
$templates = $conn->query("
    SELECT * FROM templates
    ORDER BY template_name ASC
");

/* SHOW ASSIGNED DATA */
$template_fonts_array = [];
$templates_raw = $conn->query("SELECT id, font_name FROM templates");
if ($templates_raw) {
    while ($r = $templates_raw->fetch_assoc()) {
        $template_fonts_array[$r['id']] = $r['font_name'];
    }
}

$assigned = $conn->query("
    SELECT 
        s.id as store_id,
        s.store_name,
        t.template_name,
        st.font_name
    FROM store_template st
    JOIN stores s ON st.store_id = s.id
    JOIN templates t ON st.template_id = t.id
    ORDER BY s.store_name ASC
");

$assignments_array = [];
$store_fonts_array = [];
$assigned_raw = $conn->query("SELECT store_id, template_id, font_name FROM store_template");
if ($assigned_raw) {
    while($r = $assigned_raw->fetch_assoc()) {
        $assignments_array[$r['store_id']] = $r['template_id'];
        $store_fonts_array[$r['store_id']] = $r['font_name'];
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Store Template Assign</title>

    <!-- Dynamically load fonts from Google Fonts to ensure they render properly -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
    $unique_fonts = array_unique(array_values($template_fonts_array));
    foreach ($unique_fonts as $f) {
        if (trim($f) !== '') {
            $fontNameForUrl = str_replace(' ', '+', trim($f));
            echo "<link href='https://fonts.googleapis.com/css2?family={$fontNameForUrl}:wght@400;700&display=swap' rel='stylesheet'>\n";
        }
    }
    ?>

    <style>
        body {
            font-family: Arial;
            background: #f5f6fa;
            padding: 40px;
        }

        .container {
            width: 700px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ddd;
        }

        h2 {
            margin-bottom: 20px;
        }

        select,
        input,
        button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            background: #2d89ef;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #1b5fbf;
        }

        .msg {
            background: #e8f5e9;
            color: green;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        table th {
            background: #f2f2f2;
        }
    </style>
</head>

<body>

    <div class="container">

        <h2>Assign / Update Template</h2>

        <?php if ($message != "") { ?>
            <div class="msg">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <form method="POST">

            <!-- STORE SELECT -->
            <label>Select Store</label>
            <?php $selected_store = isset($_POST['store_id']) ? $_POST['store_id'] : ''; ?>
            <select name="store_id" id="store_id" required onchange="toggleTemplateForm()">
                <option value="">Choose Store</option>

                <?php while ($row = $stores->fetch_assoc()) {
                    $sel = ($row['id'] == $selected_store) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $row['id']; ?>" <?php echo $sel; ?>>
                        <?php echo $row['store_name']; ?>
                    </option>
                <?php } ?>

            </select>

            <div id="template-form-section" style="display: none;">
                <!-- TEMPLATE SELECT -->
                <label>Select Template</label>
                <select name="template_id" id="template_id" required onchange="onTemplateChange()">
                    <option value="">Choose Template</option>

                    <?php
                    $templates->data_seek(0);
                    while ($row = $templates->fetch_assoc()) { ?>
                        <option value="<?php echo $row['id']; ?>"
                            style="font-family: '<?php echo $row['font_name']; ?>', sans-serif;">
                            <?php echo $row['template_name']; ?>
                        </option>
                    <?php } ?>

                </select>

                <!-- FONT INPUT -->
                <label>Font Name</label>
                <input type="text" name="font_name" id="font_name" placeholder="Enter font name">

                <button type="submit" name="save">
                    Assign / Update Template
                </button>
            </div>

        </form>

        <h2>Assigned Template List</h2>

        <table>
            <tr>
                <th>Store Name</th>
                <th>Assigned Template</th>
                <th>Font Name</th>
                <th>Action</th>
            </tr>

            <?php while ($row = $assigned->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['store_name']; ?></td>
                    <td style="font-family: '<?php echo $row['font_name']; ?>', sans-serif;">
                        <?php echo $row['template_name']; ?></td>
                    <td style="font-family: '<?php echo $row['font_name']; ?>', sans-serif;">
                        <?php echo $row['font_name']; ?></td>
                    <td>
                        <button type="button" onclick="editStore('<?php echo $row['store_id']; ?>')"
                            style="width: auto; padding: 6px 12px; margin: 0; background: #f39c12; color: white; border: none; border-radius: 4px; cursor: pointer;">Edit</button>
                    </td>
                </tr>
            <?php } ?>

        </table>

    </div>

    <script>
    const assignments = <?php echo json_encode($assignments_array); ?>;
    const storeFonts = <?php echo json_encode($store_fonts_array); ?>;
    const templateFonts = <?php echo json_encode($template_fonts_array); ?>;
    
    function toggleTemplateForm() {
        const storeId = document.getElementById('store_id').value;
        const templateSection = document.getElementById('template-form-section');
        const templateSelect = document.getElementById('template_id');
        const fontNameInput = document.getElementById('font_name');
        
        if (storeId) {
            templateSection.style.display = 'block';
            
            // Auto-select existing template if already assigned
            if (assignments[storeId]) {
                templateSelect.value = assignments[storeId];
                fontNameInput.value = storeFonts[storeId] || templateFonts[assignments[storeId]] || '';
            } else {
                    templateSelect.value = ''; // Reset if new assignment
                    fontNameInput.value = '';
                }
            } else {
                templateSection.style.display = 'none';
            }
        }

        function onTemplateChange() {
            const templateId = document.getElementById('template_id').value;
            const fontNameInput = document.getElementById('font_name');
            if (templateId && templateFonts[templateId] !== undefined) {
                fontNameInput.value = templateFonts[templateId];
            } else {
                fontNameInput.value = '';
            }
        }

        function editStore(storeId) {
            document.getElementById('store_id').value = storeId;
            toggleTemplateForm();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Retain form state if reloaded (e.g., after save)
        window.onload = function () {
            if (document.getElementById('store_id').value) {
                toggleTemplateForm();
            }
        }
    </script>

</body>

</html>