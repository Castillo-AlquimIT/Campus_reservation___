// app.js
document.addEventListener('DOMContentLoaded', async () => {
  // Init calendar
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 'auto',
    events: async (info, successCallback, failureCallback) => {
      try {
        const res = await fetch(`events.php?start=${info.startStr}&end=${info.endStr}`);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to load events');
        successCallback(data);
      } catch (e) {
        failureCallback(e);
      }
    }
  });
  calendar.render();

  // Populate resource list
  const resForm = document.getElementById('resForm');
  const resourceSelect = document.getElementById('resourceSelect');
  const targetTypeSelect = resForm.targetType;

  async function loadResources() {
    const endpoint = targetTypeSelect.value === 'room' ? 'rooms.php' : 'equipment.php';
    const res = await fetch(endpoint);
    const data = await res.json();
    resourceSelect.innerHTML = '';
    data.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = targetTypeSelect.value === 'room'
        ? `${item.name} (${item.location ?? 'N/A'})`
        : `${item.name} (${item.category ?? 'General'}) qty:${item.quantity}`;
      resourceSelect.appendChild(opt);
    });
  }
  targetTypeSelect.addEventListener('change', loadResources);
  loadResources();

  // Create reservation
  resForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      title: resForm.title.value.trim(),
      targetType: resForm.targetType.value,
      targetId: parseInt(resForm.targetId.value, 10),
      start: resForm.start.value.replace('T',' ') + ':00',
      end: resForm.end.value.replace('T',' ') + ':00',
      notes: resForm.notes.value.trim() || null
    };
    const res = await fetch('reservations.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('resMsg').textContent = res.ok ? data.message : (data.error || 'Error');
    if (res.ok) calendar.refetchEvents();
  });

  // Approvals
  const approveForm = document.getElementById('approveForm');
  if (approveForm) {
    approveForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        reservationId: parseInt(approveForm.reservationId.value, 10),
        toStatus: approveForm.toStatus.value,
        remarks: approveForm.remarks.value.trim()
      };
      const res = await fetch('approvals.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      document.getElementById('approveMsg').textContent = res.ok ? data.message : (data.error || 'Error');
      if (res.ok) calendar.refetchEvents();
    });
  }

  // Reports
  const reportForm = document.getElementById('reportForm');
  if (reportForm) {
    reportForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const params = new URLSearchParams();
      if (reportForm.start.value) params.append('start', reportForm.start.value + ' 00:00:00');
      if (reportForm.end.value) params.append('end', reportForm.end.value + ' 23:59:59');
      if (reportForm.room_id.value) params.append('room_id', reportForm.room_id.value);
      if (reportForm.user_id.value) params.append('user_id', reportForm.user_id.value);
      const res = await fetch('reports.php?' + params.toString());
      const data = await res.json();
      document.getElementById('reportOutput').textContent = JSON.stringify(data, null, 2);
    });
  }

  // Logout
  const logoutBtn = document.getElementById('logoutBtn');
  logoutBtn.addEventListener('click', async () => {
    await fetch('auth.php', { method: 'DELETE' });
    window.location.href = 'login.html';
  });
});

document.addEventListener('DOMContentLoaded', async () => {
  const calendarEl = document.getElementById('calendar');
  const menu = document.getElementById('eventMenu');

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 'auto',
    events: async (info, successCallback, failureCallback) => {
      try {
        const res = await fetch(`events.php?start=${info.startStr}&end=${info.endStr}`);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to load events');
        successCallback(data);
      } catch (e) {
        failureCallback(e);
      }
    },
    eventDidMount: (info) => {
      // Right-click handler
      info.el.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        menu.style.top = e.pageY + 'px';
        menu.style.left = e.pageX + 'px';
        menu.style.display = 'block';
        menu.dataset.eventId = info.event.id;
      });
    }
  });
  calendar.render();

  // Hide menu when clicking elsewhere
  document.addEventListener('click', () => {
    menu.style.display = 'none';
  });

  // Handle menu actions
  menu.addEventListener('click', async (e) => {
    const action = e.target.dataset.action;
    const reservationId = menu.dataset.eventId;

    if (action === 'delete') {
      const res = await fetch('reservations.php', {
        method: 'DELETE',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `reservationId=${reservationId}`
      });
      const data = await res.json();
      alert(data.message || data.error);
      calendar.refetchEvents();
    }

    if (action === 'archive') {
      const res = await fetch('reservations.php', {
        method: 'PATCH',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({reservationId, archive:true})
      });
      const data = await res.json();
      alert(data.message || data.error);
      calendar.refetchEvents();
    }

    menu.style.display = 'none';
  });
});
