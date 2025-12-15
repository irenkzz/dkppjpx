<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();
?>
      <footer class="main-footer">
        <div class="pull-right hidden-xs">
         
        </div>
		Copyright &copy; 2017 <?php echo $tdin['nama_pemilik']; ?>. All rights reserved.
      </footer>
	  
	  <aside class="control-sidebar control-sidebar-dark">
        <!-- Create the tabs -->
        <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
		</ul>
		<div class="tab-content">
          <!-- Home tab content -->
          <div class="tab-pane" id="control-sidebar-home-tab">
		  </div>
		</div>
	  </aside>
	  <div class="control-sidebar-bg"></div>
    </div><!-- ./wrapper -->
	
    <!-- jQuery 2.1.4 -->
    <script src="/adminweb/plugins/jQuery/jQuery-2.1.4.min.js"></script>
    <!-- Bootstrap 3.3.5 -->
    <script src="/adminweb/bootstrap/js/bootstrap.min.js"></script>
    <!-- Select2 -->
    <script src="/adminweb/plugins/select2/select2.full.min.js"></script>
    <!-- DataTables -->
    <script src="/adminweb/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="/adminweb/plugins/datatables/dataTables.bootstrap.min.js"></script>
    <!-- date-range-picker -->
    <script src="/adminweb/plugins/moment/moment.js"></script>
    <script src="/adminweb/plugins/daterangepicker/daterangepicker.js"></script>
    <!-- SlimScroll -->
    <script src="/adminweb/plugins/slimScroll/jquery.slimscroll.min.js"></script>
    <!-- iCheck 1.0.1 -->
    <script src="/adminweb/plugins/iCheck/icheck.min.js"></script>
    <!-- FastClick -->
    <script src="/adminweb/plugins/fastclick/fastclick.min.js"></script>
    <!-- ChartJS 1.0.1 -->
    <script src="/adminweb/plugins/chartjs/Chart.min.js"></script>
    <!-- AdminLTE App -->
    <script src="/adminweb/dist/js/app.min.js"></script>
    <script src="/adminweb/dist/js/yn-toggle.js"></script>
	<!-- CK Editor -->
    <script src="/adminweb/plugins/ckeditor/ckeditor.js"></script>
    <script src="/adminweb/dist/js/demo.js"></script>
    <?php if (isset($_GET['module']) && $_GET['module'] === 'beranda'): ?>
    <script>
    (function(){
      var payload = window.dashboardChart || null;
      if (!payload) return;

      function initChart() {
        try {
          if (!window.Chart) return;
          var canvas = document.getElementById('visitorsChart');
          if (!canvas) return;
          var cap = 365;
          var labels = Array.isArray(payload.labels) ? payload.labels.slice(0, cap) : [];
          var visitors = Array.isArray(payload.visitors) ? payload.visitors.slice(0, cap) : [];
          var hits = Array.isArray(payload.hits) ? payload.hits.slice(0, cap) : [];
          var len = Math.min(labels.length, visitors.length, hits.length);
          if (len <= 0) return;
          labels = labels.slice(0, len);
          visitors = visitors.slice(0, len);
          hits = hits.slice(0, len);
          var flat = visitors.concat(hits).map(function(v){ v = parseInt(v, 10); return isNaN(v) ? 0 : v; });
          var maxVal = Math.max.apply(null, flat);
          var useZeroScale = !isFinite(maxVal) || maxVal <= 0;
          var chart = new Chart(canvas.getContext('2d'));
          chart.Line({
            labels: labels,
            datasets: [
              {
                label: "Pengunjung",
                fillColor: "rgba(60,141,188,0.8)",
                strokeColor: "rgba(60,141,188,1)",
                pointColor: "#3b8bba",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(60,141,188,1)",
                data: visitors
              },
              {
                label: "Hits",
                fillColor: "rgba(0,166,90,0.4)",
                strokeColor: "rgba(0,166,90,1)",
                pointColor: "#00a65a",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(0,166,90,1)",
                data: hits
              }
            ]
          }, {
            scaleOverride: useZeroScale,
            scaleSteps: useZeroScale ? 1 : undefined,
            scaleStepWidth: useZeroScale ? 1 : undefined,
            scaleStartValue: useZeroScale ? 0 : undefined,
            showScale: true,
            scaleShowGridLines: true,
            scaleGridLineColor: "rgba(0,0,0,.05)",
            scaleGridLineWidth: 1,
            scaleShowHorizontalLines: true,
            scaleShowVerticalLines: true,
            bezierCurve: false,
            pointDot: true,
            pointDotRadius: 4,
            pointDotStrokeWidth: 1,
            pointHitDetectionRadius: 10,
            datasetStroke: true,
            datasetStrokeWidth: 2,
            datasetFill: true,
            maintainAspectRatio: true,
            responsive: true
          });
        } catch (err) {
          if (window.console && console.warn) console.warn('Visitor chart skipped:', err);
        }
      }

      if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initChart, 0);
      } else {
        document.addEventListener('DOMContentLoaded', initChart, false);
      }
    })();
    </script>
    <?php endif; ?>
    <!-- page script -->
	<script>		
      $(function () {
        $("#datamodul").DataTable();
        $('#datauser').DataTable();
        $('#datatemplates').DataTable();
        $('#datamenu').DataTable();
        $('#databerita').DataTable();
        $('#datakategori').DataTable();
        $('#datahalamanstatis').DataTable();
        $('#databanner').DataTable();
        $('#dataagenda').DataTable();
        $('#datapolling').DataTable();
        $('#datahubungi').DataTable();
        $('#dataalbum').DataTable();
        $('#datagalerifoto').DataTable();
        $('#datavideo').DataTable();
        $('#datadownload').DataTable();
		
		//Datepicker
		$('#tgl_mulai').daterangepicker({singleDatePicker: true,format: 'DD/MM/YYYY', "opens": "left"});
		$('#tgl_selesai').daterangepicker({singleDatePicker: true,format: 'DD/MM/YYYY', "opens": "left"});
		
		$(".select2").select2();
		
		$('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
          checkboxClass: 'icheckbox_minimal-blue',
          radioClass: 'iradio_minimal-blue'
        });
		
		var editorId = 'isi_<?php echo $_GET['module']; ?>';
if (document.getElementById(editorId)) {
    CKEDITOR.replace(editorId);
}

      });
    </script>
  </body>
</html>
