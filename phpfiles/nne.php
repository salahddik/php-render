<?php
$api_url = 'https://dummyjson.com/products';
$json_data = file_get_contents($api_url);
$data = json_decode($json_data, true);

if ($data) {
    echo '<table>';
    echo '<thead><tr><th>ID</th><th>Name</th><th>Price</th></tr></thead>';
    echo '<tbody>';
    foreach ($data as $item) {
        echo '<tr>';
        echo '<td>' . $item['id'] . '</td>';
        echo '<td>' . $item['name'] . '</td>';
        echo '<td>' . $item['price'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo 'Failed to fetch data from the API.';
}
?>
