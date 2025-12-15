<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

include "../config/fungsi_indotgl.php";
opendb();
include "header.php";

include "menu.php";
?>

      <!-- =============================================== -->

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
		<?php include "content.php"; ?>
      </div><!-- /.content-wrapper -->

<?php
include "footer.php";
closedb();
?>
