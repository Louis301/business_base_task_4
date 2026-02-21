// Загрузка заявок
async function loadRequests(role, statusFilter = '') {
  try {
      const response = await fetch('/api/requests');
      const requests = await response.json();
      
      const tbody = document.querySelector('#requestsTable tbody');
      tbody.innerHTML = '';
      
      requests.forEach(req => {
          if (statusFilter && req.status !== statusFilter) return;
          
          const tr = document.createElement('tr');
          tr.innerHTML = `
              <td>${req.id}</td>
              <td>${escapeHtml(req.client_name)}<br>${escapeHtml(req.phone)}</td>
              <td>${escapeHtml(req.problem_text)}<br><small>${escapeHtml(req.address)}</small></td>
              <td><span class="status status-${req.status}">${req.status}</span></td>
              <td>${req.assigned_to || '-'}</td>
              <td>${getActionButtons(req, role)}</td>
          `;
          tbody.appendChild(tr);
      });
  } catch (err) {
      console.error('Ошибка загрузки:', err);
  }
}

// Кнопки действий в зависимости от роли
function getActionButtons(req, role) {
  if (role === 'dispatcher') {
      if (req.status === 'new') {
          return `
              <button onclick="assignRequest(${req.id})">Назначить</button>
              <button onclick="cancelRequest(${req.id})">Отменить</button>
          `;
      } else if (req.status === 'assigned') {
          return `<button onclick="cancelRequest(${req.id})">Отменить</button>`;
      }
  } else if (role === 'master') {
      if (req.status === 'assigned') {
          return `<button onclick="takeRequest(${req.id})">Взять в работу</button>`;
      } else if (req.status === 'in_progress') {
          return `<button onclick="completeRequest(${req.id})">Завершить</button>`;
      }
  }
  return '-';
}

// Взять заявку в работу (с обработкой 409 Conflict)
async function takeRequest(id) {
  try {
      const response = await fetch(`/api/take/${id}`, { method: 'POST' });
      const data = await response.json();
      
      if (response.status === 409) {
          showMessage('⚠️ ' + data.error, 'error');
      } else if (response.ok) {
          showMessage('✅ Заявка взята в работу!', 'success');
          setTimeout(() => loadRequests('master'), 1000);
      } else {
          showMessage('❌ ' + data.error, 'error');
      }
  } catch (err) {
      showMessage('Ошибка сети', 'error');
  }
}

// Назначить мастера
async function assignRequest(id) {
  const masterId = prompt('Введите ID мастера (2 или 3):');
  if (!masterId) return;
  
  try {
      const response = await fetch(`/api/assign/${id}`, {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ master_id: parseInt(masterId) })
      });
      
      if (response.ok) {
          showMessage('✅ Заявка назначена!', 'success');
          setTimeout(() => loadRequests('dispatcher'), 1000);
      } else {
          const error = await response.json();
          showMessage('❌ ' + error.error, 'error');
      }
  } catch (err) {
      showMessage('Ошибка сети', 'error');
  }
}

// Отменить заявку
async function cancelRequest(id) {
  if (!confirm('Отменить заявку?')) return;
  
  try {
      const response = await fetch(`/api/cancel/${id}`, { method: 'POST' });
      if (response.ok) {
          showMessage('✅ Заявка отменена!', 'success');
          setTimeout(() => loadRequests('dispatcher'), 1000);
      }
  } catch (err) {
      showMessage('Ошибка сети', 'error');
  }
}

// Завершить заявку
async function completeRequest(id) {
  try {
      const response = await fetch(`/api/complete/${id}`, { method: 'POST' });
      if (response.ok) {
          showMessage('✅ Заявка завершена!', 'success');
          setTimeout(() => loadRequests('master'), 1000);
      }
  } catch (err) {
      showMessage('Ошибка сети', 'error');
  }
}

// Утилиты
function showMessage(text, type) {
  const div = document.getElementById('message');
  if (div) {
      div.className = type;
      div.textContent = text;
      setTimeout(() => div.textContent = '', 3000);
  } else {
      alert(text);
  }
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}