<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<div class="tabular--wrapper">
    <div class="add">
        <div class="filter">
            <form method="GET" action="">
                <input type="text" name="search_user" placeholder="Search user..." 
                    value="<?php echo isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : ''; ?>" 
                    style="width: 174px; margin-right: 10px; height:37px; margin-top: 6px;" 
                    onkeydown="if(event.key === 'Enter') this.form.submit();"
                >  <!-- //SEARCH BAR -->
               
                <select name="academic_year_id" onchange="this.form.submit()" 
                    style="width: 214px; margin-right: 10px; height: 37px; margin-top: 6px;"> <!-- //ACAD YEAR -->

                    <option value="" selected>Select Academic Year</option>
                    <?php
                    $academicYearQuery = "SELECT academic_year_id, academic_year FROM academic_years";
                    $academicYearResult = $con->query($academicYearQuery);
                    if ($academicYearResult && $academicYearResult->num_rows > 0) {
                        while ($row = $academicYearResult->fetch_assoc()) {
                            $selected = (isset($_GET['academic_year_id']) && $_GET['academic_year_id'] == $row['academic_year_id']) ? 'selected' : '';
                            echo "<option value='{$row['academic_year_id']}' $selected>{$row['academic_year']}</option>";
                        }
                    }
                    ?>
                </select>

                <select name="semester_id" onchange="this.form.submit()"
                    style="width: 174px; margin-right: 10px; height: 37px; margin-top: 6px;"> <!-- //ACAD SEM -->

                    <option value="" selected>Select Semester</option>
                    <?php
                    $semesterQuery = "SELECT semester_id, semester_name FROM semesters";
                    $semesterResult = $con->query($semesterQuery);
                    if ($semesterResult && $semesterResult->num_rows > 0) {
                        while ($row = $semesterResult->fetch_assoc()) {
                            $selected = (isset($_GET['semester_id']) && $_GET['semester_id'] == $row['semester_id']) ? 'selected' : '';
                            echo "<option value='{$row['semester_id']}' $selected>{$row['semester_name']}</option>";
                        }
                    }
                    ?>
                </select>
            </form>
        </div>
    </div>
    <?php
    // Pagination setup
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max($page, 1);
    $offset = ($page - 1) * $limit;

    // Filters - Sanitize inputs to prevent SQL injection vulnerabilities
    $search_user = isset($_GET['search_user']) ? $con->real_escape_string($_GET['search_user']) : '';
    $academic_year_id = isset($_GET['academic_year_id']) ? $con->real_escape_string($_GET['academic_year_id']) : '';
    $semester_id = isset($_GET['semester_id']) ? $con->real_escape_string($_GET['semester_id']) : '';

    // Base WHERE clause
    $whereClauses = ["employee_role.role_id = 2"]; // Faculty role only

    // Add search filters
    if ($search_user) {
        $whereClauses[] = "(employee.firstName LIKE ? OR employee.lastName LIKE ?)";
    }
    if ($academic_year_id) {
        $whereClauses[] = "itl_extracted_data.academic_year_id = ?";
    }
    if ($semester_id) {
        $whereClauses[] = "itl_extracted_data.semester_id = ?";
    }

    $whereClause = !empty($whereClauses) ? implode(' AND ', $whereClauses) : '1'; // Default WHERE clause

    // Sorting
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    $order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
    $sortColumn = ($sort === 'name') ? "CONCAT(employee.firstName, ' ', employee.lastName)" : 'itl_extracted_data.totalOverload';

    // Total rows for pagination
    $totalQuery = "SELECT COUNT(*) as total FROM employee 
        INNER JOIN employee_role ON employee.userId = employee_role.userId 
        LEFT JOIN itl_extracted_data ON employee.userId = itl_extracted_data.userId 
        WHERE $whereClause";

    $stmt_total = $con->prepare($totalQuery);
    $params_total = [];
    if ($search_user) {
        $params_total[] = "%$search_user%";
        $params_total[] = "%$search_user%";
    }
    if ($academic_year_id) {
        $params_total[] = $academic_year_id;
    }
    if ($semester_id) {
        $params_total[] = $semester_id;
    }

    if (!empty($params_total)) {
        $types_total = str_repeat('s', count($params_total));
        $stmt_total->bind_param($types_total, ...$params_total);
    }

    $stmt_total->execute();
    $totalResult = $stmt_total->get_result();
    $totalRows = $totalResult->fetch_assoc()['total'] ?? 0;
    $totalPages = max(ceil($totalRows / $limit), 1);

    // Data query
    $sql = "SELECT employee.employeeId, employee.firstName, employee.middleName, employee.lastName, 
        itl_extracted_data.id, itl_extracted_data.userId, itl_extracted_data.totalOverload, 
        itl_extracted_data.designated, academic_years.academic_year, semesters.semester_name, 
        itl_extracted_data.filePath, itl_extracted_data.facultyCredit, itl_extracted_data.allowableUnit 
        FROM employee 
        JOIN itl_extracted_data ON employee.userId = itl_extracted_data.userId 
        LEFT JOIN employee_role ON employee.userId = employee_role.userId 
        LEFT JOIN academic_years ON itl_extracted_data.academic_year_id = academic_years.academic_year_id 
        LEFT JOIN semesters ON itl_extracted_data.semester_id = semesters.semester_id 
        WHERE $whereClause ORDER BY $sortColumn $order LIMIT ? OFFSET ?";

    $stmt = $con->prepare($sql);
    $params = [];
    if ($search_user) {
        $params[] = "%$search_user%";
        $params[] = "%$search_user%";
    }
    if ($academic_year_id) {
        $params[] = $academic_year_id;
    }
    if ($semester_id) {
        $params[] = $semester_id;
    }
    $params[] = $limit;
    $params[] = $offset;

    if (!empty($params)) {
        $types = str_repeat('s', count($params) - 2) . 'ii';
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    ?>

    <table>
        <thead>
        <tr>
            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">No.</th>

            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Name
            <a href="?sort=name&order=asc" class="sort-arrow <?php echo $sort === 'name' && $order === 'asc' ? 'active' : ''; ?>">▲</a>
            <a href="?sort=name&order=desc" class="sort-arrow <?php echo $sort === 'name' && $order === 'desc' ? 'active' : ''; ?>">▼</a>
            </th>

            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Designation</th>
            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left; white-space: nowrap;">Academic Year</th>
            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Semester</th>
            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Faculty Credit</th>
            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Allowable Unit</th>

            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left; white-space: nowrap;">Actual Overload
                <a href="?sort=designated&order=asc" class="sort-arrow <?php echo $sort === 'designated' && $order === 'asc' ? 'active' : ''; ?>">▲</a>
                <a href="?sort=designated&order=desc" class="sort-arrow <?php echo $sort === 'designated' && $order === 'desc' ? 'active' : ''; ?>">▼</a>
            </th>

            <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Action</th>
        </tr>

        </thead>
        <tbody>
        <?php
            if ($result && $result->num_rows > 0) {
                $counter = $offset;
                while ($row = $result->fetch_assoc()) {
                    $counter++;
                    $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']);
                    $fileUploaded = !empty($row['filePath']); // Assuming 'filePath' is in the database.
                    $downloadLink = $fileUploaded ? 'uploads/' . htmlspecialchars($row['filePath']) : null;

                    if (!$fileUploaded) {
                        $totalOverload = '--'; 
                    } else {
                        $totalOverload = isset($row['totalOverload']) 
                            ? ($row['totalOverload'] < 0 
                                ? "<span style='color: red;'>Underload</span>" // Display underload in red
                                : ($row['totalOverload'] == 0 
                                    ? "<span style='color: green;'>Regular Load</span>" 
                                    : htmlspecialchars($row['totalOverload'])))
                            : "<span style='color: red;'>No Overload</span>"; 
                    }
                    

                    echo "<tr>
                        <td>$counter</td>
                        <td>" . htmlspecialchars($fullName) . "</td>
                        <td>" . htmlspecialchars($row['designated']) . "</td>
                        <td>" . htmlspecialchars($row['academic_year']) . "</td>
                        <td>" . htmlspecialchars($row['semester_name']) . "</td>
                        <td>" . htmlspecialchars($row['facultyCredit']) . "</td>
                        <td>" . htmlspecialchars($row['allowableUnit']) . "</td>
                       <td style='text-align:center; font-weight: bolder;'>" . $totalOverload . "</td>
                        <td style='font-size:19px;' >
                           " . ($downloadLink ? "<a href='$downloadLink' class='action download-link' style='text-decoration: none;' download title='Download the file'>
                           <i class='bx bxs-download'></i></a>" : 
                                "<span class='text-muted'>No file</span>") . "

                            
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='9' style='text-align:center;'>No records found.</td></tr>";
            }
            ?>


        </tbody>
    </table>

    <!-- Pagination Buttons -->
    <div class="pagination" id="pagination">
            <?php if ($totalPages > 1): ?>
                <a href="?page=1" class="pagination-button">&laquo;</a>
                <a href="?page=<?php echo max(1, $page - 1); ?>" class="pagination-button">&lsaquo;</a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="pagination-button <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a href="?page=<?php echo min($totalPages, $page + 1); ?>" class="pagination-button">&rsaquo;</a>
                <a href="?page=<?php echo $totalPages; ?>" class="pagination-button">&raquo;</a>
            <?php endif; ?>
        </div>

    
</div>

</div>

<?php
include('./includes/footer.php');
?>
