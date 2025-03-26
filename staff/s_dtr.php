<?php
include('./includes/authentication.php');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    $query = "DELETE FROM dtr_extracted_data WHERE id = ?";
    $stmt = $con->prepare($query);

    if ($stmt === false) {
        die("Error preparing query: " . $con->error);
    }   

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: s_dtr.php?deleted=true");
        exit();
    } else {
        echo "Error deleting record: " . $stmt->error;
    }

    $stmt->close();
}


include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">


<div class="tabular--wrapper">
    <!-- Success/Error Message Display -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" id="successMessage">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger" id="errorMessage">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>


    <div class="add">
        <div class="filter">
            <form method="GET" action="" class="d-flex align-items-center">
            <input type="text" name="search_user" placeholder="Search user..." 
                    value="<?php echo isset($_GET['search_user']) ? $_GET['search_user'] : ''; ?>" 
                    style="width: 200px; margin-right: 10px; height: 43px;">

            <select name="academic_year" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 220px;">
                <option value="" selected>Select Academic Year</option>
                <?php
                $academicYearQuery = "SELECT * FROM academic_years";
                $academicYearResult = $con->query($academicYearQuery);
                while ($academicYear = $academicYearResult->fetch_assoc()):
                ?>
                <option value="<?php echo $academicYear['academic_year_id']; ?>" 
                    <?php echo (isset($_GET['academic_year']) && $_GET['academic_year'] == $academicYear['academic_year_id']) ? 'selected' : ''; ?>>
                    <?php echo $academicYear['academic_year']; ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select name="semester" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 180px;">
                <option value="" selected>Select Semester</option>
                <?php
                $semesterQuery = "SELECT * FROM semesters";
                $semesterResult = $con->query($semesterQuery);
                while ($semester = $semesterResult->fetch_assoc()):
                ?>
                <option value="<?php echo $semester['semester_id']; ?>" 
                    <?php echo (isset($_GET['semester']) && $_GET['semester'] == $semester['semester_id']) ? 'selected' : ''; ?>>
                    <?php echo $semester['semester_name']; ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select name="dept_filter" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 190px;">
                <option value="" disabled selected>Select Department</option>
                <option value="ALL" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == 'ALL') echo 'selected'; ?>>All</option>
                <option value="1" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == '1') echo 'selected'; ?>>IT</option>
                <option value="3" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == '3') echo 'selected'; ?>>CS</option>
                <option value="4" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == '4') echo 'selected'; ?>>DS</option>
                <option value="2" <?php if (isset($_GET['dept_filter']) && $_GET['dept_filter'] == '2') echo 'selected'; ?>>TCM</option>
            </select>
            </form>
        </div>

        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class='bx bxs-file-import'></i>
            <span class="text">Import DTR</span>
        </button>

        </div>
        <div class="table-container">
            <?php
                $search_user = isset($_GET['search_user']) ? $_GET['search_user'] : '';
                $academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
                $semester = isset($_GET['semester']) ? $_GET['semester'] : '';
                $deptFilter = $_GET['dept_filter'] ?? null;
                $department_id = $_GET['dept_id'] ?? '';
                
                $maxHours = 40;
                $creditThreshold = 12;
                
                $whereClauses = [];
                
                $query = "
                    SELECT DISTINCT 
                        itl.id as itl_id, e.userId, e.firstName, e.middleName, e.lastName, e.employeeId,
                        itl.designated, itl.totalOverload, itl.academic_year_id as itl_academic_year_id,
                        itl.semester_id as itl_semester_id, d.id as dtr_id, d.academic_year_id,
                        d.semester_id, d.week1, d.week2, d.week3, d.week4, d.week5, d.filePath,
                        d.month_year, d.week1_overload, d.week2_overload, d.week3_overload, 
                        d.week4_overload, a.academic_year, s.semester_name,
                        dept.dept_id, e.department, dept.departmentName
                    FROM itl_extracted_data itl
                    JOIN employee e ON itl.userId = e.userId 
                    JOIN dtr_extracted_data d ON e.userId = d.userId 
                    JOIN department dept ON e.department = dept.dept_id
                    LEFT JOIN academic_years a ON CASE 
                        WHEN itl.academic_year_id = 6 THEN 6
                        ELSE d.academic_year_id 
                    END = a.academic_year_id
                    LEFT JOIN semesters s ON d.semester_id = s.semester_id
                    WHERE (
                        (itl.academic_year_id = d.academic_year_id AND itl.semester_id = d.semester_id)
                        OR 
                        (itl.academic_year_id = 6 AND d.academic_year_id = 1 AND itl.semester_id = d.semester_id)
                    )";
                
                // Apply search filters
                if (!empty($search_user)) {
                    $search_user = $con->real_escape_string($search_user);
                    $whereClauses[] = "(e.firstName LIKE '%$search_user%' 
                                        OR e.middleName LIKE '%$search_user%' 
                                        OR e.lastName LIKE '%$search_user%' 
                                        OR e.employeeId LIKE '%$search_user%')";
                }
                if (!empty($academic_year)) {
                    $whereClauses[] = "d.academic_year_id = $academic_year";
                }
                if (!empty($semester)) {
                    $whereClauses[] = "d.semester_id = $semester";
                }
                if ($deptFilter && $deptFilter !== 'ALL') {
                    $whereClauses[] = "e.department = '$deptFilter'";
                }
                
                // Add additional filters if there are any
                if (!empty($whereClauses)) {
                    $query .= " AND " . implode(' AND ', $whereClauses);
                }
                
                // Grouping condition
                $query .= " GROUP BY itl.id, d.id";
 
                // SORT ARROW
                $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name'; 
                $order = isset($_GET['order']) ? $_GET['order'] : 'asc';


                switch ($sort) {
                    case 'name':
                        $query .= " ORDER BY e.firstName " . $order . ", e.middleName " . $order . ", e.lastName " . $order;
                        break;
                    case 'totalOverload':
                        $query .= " ORDER BY itl.totalOverload " . $order;
                        break;
                    case 'designated':
                        $query .= " ORDER BY itl.designated " . $order;
                        break;
                    default:
                        $query .= " ORDER BY e.firstName " . $order;
                }

            // PAGINATION
                $limit = 10;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $page = max($page, 1);
                $offset = ($page - 1) * $limit;

                $query .= " LIMIT $offset, $limit";

                $result = $con->query($query);

                if (!$result) {
                    die("Error fetching data: " . $con->error);
                }

                $totalQuery = "
                    SELECT COUNT(*) as total
                    FROM itl_extracted_data itl
                    JOIN employee e ON itl.userId = e.userId
                    JOIN dtr_extracted_data d ON e.userId = d.userId AND (
                        (itl.semester_id = 1 AND d.semester_id = 1) OR 
                        (itl.semester_id = 2 AND d.semester_id = 2)
                    )
                    WHERE 1=1";

                $totalResult = $con->query($totalQuery);
                $totalRows = $totalResult->fetch_assoc()['total'] ?? 0;
                $totalPages = ceil($totalRows / $limit);

                if (!empty($search_user)) {
                    $search_user = $con->real_escape_string($search_user);
                    $totalQuery .= " AND (e.firstName LIKE '%$search_user%' 
                                        OR e.middleName LIKE '%$search_user%' 
                                        OR e.lastName LIKE '%$search_user%' 
                                        OR e.employeeId LIKE '%$search_user%')";
                }

                if (!empty($academic_year)) {
                    $totalQuery .= " AND d.academic_year_id = $academic_year";
                }

                if (!empty($semester)) {
                    $totalQuery .= " AND d.semester_id = $semester";
                }

                
                $counter = 1;

            ?>

            <table class="table-container">
                <thead>
                <tr>
                    <th style="font-size:13px; font-weight:bolder; color: white;text-align: left;">No.</th>

                    <th style="font-size:13px; font-weight:bolder; color: white; text-align: left; white-space: nowrap">Name
                        <a href="?sort=name&order=asc" class="sort-arrow <?php echo $sort === 'name' && $order === 'asc' ? 'active' : ''; ?>">▲</a>
                        <a href="?sort=name&order=desc" class="sort-arrow <?php echo $sort === 'name' && $order === 'desc' ? 'active' : ''; ?>">▼</a>
                    </th>

                    <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Department</th>
                    <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Designation</th>
                    <th style="font-size:13px; font-weight:bolder; color: white;text-align: left; ">Sem/A.Y</th>
                    <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Month/Year</th>
                    <th style="font-size:13px; font-weight:bolder; color: white;text-align: left;">Actual Overload</th>
                    <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Week 1</th>
                    <th style="font-size:13px; font-weight:bolder; color: white;text-align: left;">Week 2</th>
                    <th style="font-size:13px; font-weight:bolder; color: white;text-align: left;">Week 3</th>
                    <th style="font-size:13px; font-weight:bolder; color: white;text-align: left;">Week 4</th>
                    <th style="font-size:13px; font-weight:bolder; color: white;text-align: left;">Total Credits</th>

                    <th style="font-size:13px; font-weight:bolder; color: white; text-align: left; white-space: nowrap">Overload
                        <a href="?sort=totalOverload&order=asc" class="sort-arrow <?php echo $sort === 'totalOverload' && $order === 'asc' ? 'active' : ''; ?>">▲</a>
                        <a href="?sort=totalOverload&order=desc" class="sort-arrow <?php echo $sort === 'totalOverload' && $order === 'desc' ? 'active' : ''; ?>">▼</a>
                    </th>


                    <th style="font-size:13px; font-weight:bolder; color: white; text-align: left;">Action</th>
                </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        while ($row = $result->fetch_assoc()): 
                            $weeks = [ // Weekly hours
                                'week1' => $row['week1'],
                                'week2' => $row['week2'],
                                'week3' => $row['week3'],
                                'week4' => $row['week4'],
                                'week5' => $row['week5'],
                            ];

                            // File path and file-related logic
                            $filePath = $row['filePath'] ?? '';
                            $fileUploaded = !empty($filePath);
                            $deleteDisabled = !$fileUploaded ? 'style="pointer-events: none; color: gray;"' : '';
                            $downloadLink = $fileUploaded ? './uploads/' . $filePath : '--';
                            $downloadDisabled = !$fileUploaded ? 'style="pointer-events: none; color: gray;"' : '';

                            $totalOverload = $row['totalOverload'];
                            $excess = [];
                            $overload = [];

                            foreach ($weeks as $key => $weekHours) {
                                if ($weekHours > $maxHours) {
                                    $overload[$key] = round($weekHours - $maxHours, 2); // Retrieved hours
                                    $excess[$key] = round($weekHours - $maxHours - $totalOverload, 2); // Weekly overload
                                } else {
                                    $overload[$key] = 0;
                                    $excess[$key] = 0;
                                }
                            }

                            $totalCredits = 0;
                            $weekOverloads = 0;
                            $totalCreditsPerWeek = [];

                            foreach (['week1_overload', 'week2_overload', 'week3_overload', 'week4_overload'] as $week) {
                                $weekOverloads += is_numeric($row[$week]) ? $row[$week] : 0;

                                $totalCreditsForWeek = 0;

                                if (is_numeric($row[$week]) && $row[$week] > 12) {
                                    $totalCreditsForWeek = $row[$week] - 12;
                                    $totalCredits += $totalCreditsForWeek; // Ensure numeric addition
                                }

                                $totalCreditsPerWeek[$week] = $totalCreditsForWeek;
                            }

                            if ($totalCredits > 0) {
                                $weekOverloads -= $totalCredits;
                                $weekOverloads = max($weekOverloads, 0);
                            }
                        ?>

                        <tr>
                            <td><?php echo $counter++; ?></td> 

                            <td>
                                <?php echo htmlspecialchars($row['firstName'] . ' ' . $row['middleName'] . ' '); ?>
                                <span>
                                    <span style="font-weight: 550;"><?php echo htmlspecialchars($row['lastName'][0]);?></span><?php echo htmlspecialchars(substr($row['lastName'], 1));?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($row['departmentName']); ?></td>
                            <td><?php echo htmlspecialchars($row['designated']); ?></td>
                            
                            <td>
                                <span style="font-weight: 550;"><?php echo htmlspecialchars($row['semester_name']); ?></span>
                                <span style="font-weight: 450;"><?php echo htmlspecialchars($row['academic_year']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                            <td style="<?php echo $row['totalOverload'] < 0 ? '' : ''; ?>">
                                <?php echo htmlspecialchars($row['totalOverload']); ?>
                            </td>

                            <?php foreach (['week1', 'week2', 'week3', 'week4'] as $week): ?>
                                <td>
                                    <strong>OL:</strong> <br>
                                    <?php echo htmlspecialchars($row[$week . '_overload']); ?> <br>
                                    <strong>
                                        <?php
                                        echo ($row['designated'] === 'Designated') ? 'SC' : 
                                            (($row['designated'] === 'Non-Designated') ? 'CTO' : 'SC/CTO');
                                        ?>:
                                    </strong> <br>
                                    <?php echo htmlspecialchars($totalCreditsPerWeek[$week . '_overload'] ?? 0); ?>
                                </td>
                            <?php endforeach; ?>

                            <td>
                                <?php echo ($totalCredits > 0) ? htmlspecialchars($totalCredits) : '0'; ?>
                            </td>
                            <td style=" text-align: center; font-weight:bolder;">
                                <?php echo ($weekOverloads > 0) ? htmlspecialchars($weekOverloads) : '0'; ?>
                            </td>
                            
                            <td style="font-size:18px; white-space: nowrap;">
                                <a href="<?php echo htmlspecialchars($downloadLink); ?>" 
                                class="action download-link" 
                                <?php if (!$fileUploaded): ?> style="font-size:14px; pointer-events: none; color: gray;" <?php endif; ?> 
                                download title="Download the file"> <i class="bx bxs-download"></i> 
                                </a>

                                <a href="#" 
                                onclick="return confirmDelete(<?php echo htmlspecialchars($row['dtr_id']); ?>)" 
                                class="action delete-link" 
                                <?php if (!$fileUploaded): ?> style="font-size:14px; pointer-events: none; color: gray;" <?php endif; ?> 
                                title="Delete this record"  style="color: red;"> <i class="bx bxs-trash"></i> 
                                </a>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                        
                    <?php else: ?>
                        <tr>
                            <td colspan="15" style="text-align:center;">No records found</td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>
            <!-- Pagination -->
            <div class="pagination" id="pagination">
                <?php
                    if ($totalPages > 1) {
                        echo '<a href="?page=1" class="pagination-button">&laquo;</a>';
                        $prevPage = max(1, $page - 1);
                        echo '<a href="?page=' . $prevPage . '" class="pagination-button">&lsaquo;</a>';
                        for ($i = 1; $i <= $totalPages; $i++) {
                            $activeClass = ($i == $page) ? 'active' : '';
                            echo '<a href="?page=' . $i . '" class="pagination-button ' . $activeClass . '">' . $i . '</a>';
                        }
                        $nextPage = min($totalPages, $page + 1);
                        echo '<a href="?page=' . $nextPage . '" class="pagination-button">&rsaquo;</a>';
                        echo '<a href="?page=' . $totalPages . '" class="pagination-button">&raquo;</a>';
                    }
                ?>
            </div>
        </div>
        
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Import Daily Time Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="./controller/import-dtr.php" method="POST" enctype="multipart/form-data" id="importForm">
                            <div class="mb-3">
                                <label for="userId" class="form-label">Select Faculty</label>
                                <select class="form-control" id="userId" name="userId" required>
                                    <option value="" disabled selected>--- Select Faculty ---</option>
                                    <?php
                                        $query = "
                                            SELECT employee.userId, employee.employeeId, employee.firstName, 
                                            employee.middleName, employee.lastName 
                                            FROM employee 
                                            INNER JOIN employee_role ON employee.userId = employee_role.userId 
                                            WHERE employee_role.role_id = 2 
                                            ORDER BY employee.firstName ASC"; // Sort alphabetically by firstName

                                        $result = $con->query($query);

                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $fullName = $row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName'];
                                                echo "<option value='" . $row['userId'] . "'>" . htmlspecialchars($fullName) . "</option>";
                                            }
                                        } else {
                                            echo "<option value=''>No users found</option>";
                                        }
                                    ?>

                                </select>
                            </div>

                            <?php
                                $sql = "SELECT academic_year_id, academic_year FROM academic_years";
                                $result = $con->query($sql);

                                if ($result->num_rows > 0) {
                                    $academicYears = [];
                                    while ($row = $result->fetch_assoc()) {
                                        $academicYears[] = $row;
                                    }
                                } else {
                                    echo "No academic years found.";
                                }
                            ?>

                            <div class="mb-3">
                                <label for="academic_year" class="form-label">Select Academic Year</label>
                                <select class="form-control" id="academic_year" name="academic_year_id" required>
                                    <option value="" selected>Select Academic Year</option>
                                    <?php
                                    foreach ($academicYears as $year) {
                                        echo '<option value="' . $year['academic_year_id'] . '">' . $year['academic_year'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <?php
                                $sql = "SELECT semester_id, semester_name FROM semesters";
                                $result = $con->query($sql);

                                if ($result->num_rows > 0) {
                                    $semesters = [];
                                    while ($row = $result->fetch_assoc()) {
                                        $semesters[] = $row;
                                    }
                                } else {
                                    echo "<option value=''>No semesters found</option>";
                                }
                            ?>
                            <div class="mb-3">
                                <label for="semester" class="form-label">Select Semester</label>
                                <select class="form-control" id="semester" name="semester_id" required>
                                    <option value="" selected>Select Semester</option>
                                    <?php
                                    foreach ($semesters as $semester) {
                                        echo '<option value="' . $semester['semester_id'] . '">' . $semester['semester_name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="file" class="form-label">Upload File</label>
                                <input type="file" class="form-control" id="file" name="file" required>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Import</button>
                            </div>
                       
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
include('./includes/footer.php');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('deleted') && urlParams.get('deleted') === 'true') {
        Swal.fire({
            title: 'Deleted!',
            text: 'The record has been deleted successfully.',
            icon: 'success',
            confirmButtonColor: '#3085d6',
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "s_dtr.php?id=" + id;
            }
        });
    }
</script>