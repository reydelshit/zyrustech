<?php

// Create a database connection
$conn = mysqli_connect(DATABASE_SERVER, DATABASE_USER_NAME, DATABASE_PASSWORD, DATABASE_NAME, DATABASE_PORT);

// Check if the connection was successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if a filter is set
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Prepare the SQL query to fetch the sales report from the orders table based on the filter
$sql = "SELECT o.id, SUM(o.amount) as total_amount, o.type, o.payment_method, p.name, c.name 
        FROM orders AS o
        INNER JOIN order_product AS op ON o.id = op.order_id
        INNER JOIN product AS p ON op.product_id = p.id
        INNER JOIN category AS c ON p.category_id = c.id";

// Add the filter conditions to the SQL query if a filter is set
if ($filter === 'day') {
    $sql .= " WHERE DATE(o.created_at) = CURDATE()";
} elseif ($filter === 'week') {
    $sql .= " WHERE YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
} elseif ($filter === 'month') {
    $sql .= " WHERE MONTH(o.created_at) = MONTH(CURDATE())";
}

$sql .= " GROUP BY o.id";

// Execute the query
$result = mysqli_query($conn, $sql);

// Check if the query execution was successful
if ($result) {
    // Generate the sales report
    $sales_report = array();
    $sales_total = 0; // Variable to calculate the sales total

    // Loop through the result set
    while ($row = mysqli_fetch_assoc($result)) {
        $order = array(
            'id' => $row['id'],
            'amount' => $row['total_amount'],
            'type' => $row['type'],
            'payment_method' => $row['payment_method'],
            'product_name' => $row['name'],
            'name' => $row['name'] // Include category name
        );

        // Add the order to the sales report
        $sales_report[] = $order;

        // Add the order amount to the sales total
        $sales_total += $order['amount'];
    }

    // Close the database connection
    mysqli_close($conn);

    // Display the sales report
    echo "<h2>Sales Report</h2>";
    echo "<form action='' method='get' style='margin-bottom: 20px; display: flex; flex-direction: row; justify-content: end; align-items: center;'>";
    echo "<label for='filter'>Filter:</label>";
    echo "<select id='filter' name='filter' onchange='this.form.submit()'>";
    echo "<option value=''>All</option>";
    echo "<option value='day' " . ($filter === 'day' ? 'selected' : '') . ">Day</option>";
    echo "<option value='week' " . ($filter === 'week' ? 'selected' : '') . ">Week</option>";
    echo "<option value='month' " . ($filter === 'month' ? 'selected' : '') . ">Month</option>";
    echo "</select>";
    echo "</form>";

    echo "<br />";

    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th style='border: 1px solid #000; padding: 8px;'>ID</th>";
    echo "<th style='border: 1px solid #000; padding: 8px;'>Type</th>";
    echo "<th style='border: 1px solid #000; padding: 8px;'>Payment Method</th>";
    echo "<th style='border: 1px solid #000; padding: 8px;'>Product</th>";
    echo "<th style='border: 1px solid #000; padding: 8px;'>Category</th>"; // Category column
    echo "<th style='border: 1px solid #000; padding: 8px;'>Amount</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($sales_report as $order) {
        echo "<tr>";
        echo "<td style='border: 1px solid #000; padding: 8px;'>" . $order['id'] . "</td>";
        echo "<td style='border: 1px solid #000; padding: 8px;'>" . $order['type'] . "</td>";
        echo "<td style='border: 1px solid #000; padding: 8px;'>" . $order['payment_method'] . "</td>";
        echo "<td style='border: 1px solid #000; padding: 8px;'>" . $order['product_name'] . "</td>";
        echo "<td style='border: 1px solid #000; padding: 8px;'>" . $order['name'] . "</td>"; // Display category
        echo "<td style='border: 1px solid #000; padding: 8px;'>" . $order['amount'] . "</td>";
        echo "</tr>";
    }

    // Display the total sales row
    echo "<tr>";
    echo "<td colspan='5' style='text-align: right; border: 1px solid #000; padding: 8px;'><strong>Total Sales:</strong></td>";
    echo "<td style='border: 1px solid #000; padding: 8px;'>" . $sales_total . "</td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";

    echo "<br /><br /><br /><br /><br /><br /><br />";

    if (isset($_GET['generate_report'])) {
        // Your existing code to generate the sales report

        // Prepare the chart data
        $categories = array();
        $salesAmounts = array();

        foreach ($sales_report as $order) {
            $category = $order['name']; // Use category name
            $amount = $order['amount'];

            // If the category already exists in the array, add the amount to its corresponding index
            if (array_key_exists($category, $categories)) {
                $salesAmounts[$category] += $amount;
            }
            // Otherwise, create a new entry in both arrays
            else {
                $categories[$category] = $category;
                $salesAmounts[$category] = $amount;
            }
        }


        // Generate the bar graph
        echo "<h2>Sales by Category (Bar Graph)</h2>";
        echo "<canvas id='barChart' width='400' height='200'></canvas>";

        echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
        echo "<script>";
        echo "var ctx = document.getElementById('barChart').getContext('2d');";

        echo "var barChart = new Chart(ctx, {";
        echo "type: 'bar',";
        echo "data: {";
        echo "labels: " . json_encode(array_values($categories)) . ",";
        echo "datasets: [{";
        echo "label: 'Sales',";
        echo "data: " . json_encode(array_values($salesAmounts)) . ",";
        echo "backgroundColor: 'rgba(255, 165, 0, 0.2)',"; // Orange color
        echo "borderColor: 'rgba(255, 165, 0, 1)',"; // Orange color
        echo "borderWidth: 1";
        echo "}]";
        echo "},";
        echo "options: {";
        echo "scales: {";
        echo "y: {";
        echo "beginAtZero: true";
        echo "}";
        echo "}";
        echo "}";
        echo "});";
        echo "</script>";
    } else {
        // Display the button to generate the report
        echo "<form method='GET' style='margin-bottom: 20px; display: flex; flex-direction: row; justify-content: end; align-items: center;'>";
        echo "<button type='submit' name='generate_report' value='true' >Generate Report</button>";
        echo "</form>";
    }
} else {
    // Display an error message if the query execution failed
    echo "Error: " . mysqli_error($conn);
}
