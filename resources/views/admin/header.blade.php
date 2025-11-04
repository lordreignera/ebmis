<!-- Top Brand Bar -->
<div class="row p-0 m-0" style="background: linear-gradient(90deg, #0a183d 0%, #1a237e 100%); height: 50px;">
  <div class="col-md-12 p-0 m-0">
    <div class="card-body d-flex align-items-center justify-content-between" style="padding: 8px 20px;">
      <div class="d-flex align-items-center">
        <i class="mdi mdi-bank me-2 text-white" style="font-size: 24px;"></i>
        <h5 class="mb-0 text-white font-weight-bold">EBIMS</h5>
        <span class="ms-2 text-white-50" style="font-size: 12px;">| Enterprise Banking & Investment Management System</span>
      </div>
      <div class="d-flex align-items-center text-white-50" style="font-size: 12px;">
        <i class="mdi mdi-calendar me-1"></i>
        <span>{{ date('l, F j, Y') }}</span>
        <span class="mx-2">|</span>
        <i class="mdi mdi-clock-outline me-1"></i>
        <span id="current-time"></span>
      </div>
    </div>
  </div>
</div>

<script>
  // Update time every second
  function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('current-time').textContent = timeString;
  }
  updateTime();
  setInterval(updateTime, 1000);
</script>