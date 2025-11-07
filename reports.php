
<?php
include 'db.php'; // expects $conn (mysqli)

$sections = [];
if ($res = $conn->query("SELECT id, name, grade_level, image FROM sections ORDER BY id DESC")) {
  while ($r = $res->fetch_assoc()) $sections[] = $r;
  $res->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WMSU Attendance Tracking</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div class="main">
    <header>
      <h2>Wmsu Attendance Tracking</h2>
      <div class="admin-info">    
        <div class="admin-icon">👤 Admin</div>
      </div>
    </header>
<style>
    /* Match camera.php header and main sizing */
    .main { flex: 1; padding: 20px; box-sizing: border-box; }
    header {
      background-color: #b30000; color: white; padding: 10px 20px;
      border-radius: 8px; display: flex; justify-content: space-between; align-items: center;
    }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .rect-bottom { position: relative; }
    .delete-btn {
      position: absolute;
      right: 8px;
      top: 6px;
      border: none;
      background: transparent;
      color: #b30000;
      font-size: 18px;
      cursor: pointer;
      padding: 2px 6px;
    }
    .delete-btn:hover { color: #800; }
        .container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 5px;
            margin-top: 10px;
        }

        .rectangle {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 250px;
        }

        .rect-top {
            flex: 0 0 190px; /* Narrow height for image section */
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rect-top img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .rect-bottom {
            flex: 5;
            background-color: #fff;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        } body {
      font-family: Arial, sans-serif;
      background: #f2f2f2;
    }

    /* Add Section Button */
    .add-section-btn {
  background-color: #b30000;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: bold;
}

.button-container {
  display: flex;
  justify-content: flex-end; /* pushes button to the right */
margin-right: 10px;
}

    .add-section-btn:hover {
      background-color: #990000;
    }

    /* Modal Background */
    .modal {
      display: none; 
      position: fixed; 
      z-index: 1000; 
      left: 0;
      top: 0;
      width: 100%; 
      height: 100%; 
      background-color: rgba(0, 0, 0, 0.5); 
    }

    /* Modal Content */
    .modal-content {
      background: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 10px;
      width: 400px;
      text-align: center;
      position: relative;
    }

    .modal-content h2 {
      margin-bottom: 15px;
    }

    .modal-content label {
      display: block;
      margin: 10px 0 5px;
      text-align: left;
    }

    .modal-content input, 
    .modal-content select {
      width: 95%;
      padding: 8px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .modal-content .btn-group {
      display: flex;
      justify-content: center;
      gap: 10px;
    }

    .modal-content button {
      padding: 8px 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
    }

    .modal-content .add-btn {
      background: #b30000;
      color: white;
    }

    .modal-content .add-btn:hover {
      background: #990000;
    }

    .modal-content .cancel-btn {
      background: #aaa;
      color: white;
    }

    .modal-content .cancel-btn:hover {
      background: #888;
    }

    /* Close button (X) */
    .close {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 20px;
      cursor: pointer;
      color: red;
      font-weight: bold;
    }
    </style>
    <div class="container">
      <?php if (empty($sections)): ?>
        <p style="padding:10px;">No sections found. Add one using the button below.</p>
      <?php else: ?>
        <?php foreach ($sections as $row):
          $img = (!empty($row['image'])) ? $row['image'] : './images/peridot.jpg';
          $nameEsc = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
          $gradeEsc = htmlspecialchars($row['grade_level'], ENT_QUOTES, 'UTF-8');
        ?>
        <div class="rectangle" data-id="<?php echo (int)$row['id']; ?>">
          <div class="rect-top"><img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $nameEsc; ?>"></div>
          <div class="rect-bottom">
            Grade <?php echo $gradeEsc; ?> — <?php echo $nameEsc; ?>
            <button class="delete-btn" data-id="<?php echo (int)$row['id']; ?>" title="Delete section">🗑</button>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  <!-- Add Section Button -->
  <div class="button-container">
  <button class="add-section-btn" id="openModal">Add Section</button>
</div>
 <!-- Confirm Delete Modal -->
  <div id="confirmModal" class="modal">
    <div class="modal-content" style="width:360px;">
      <h3 style="margin-top:0;">Delete Section</h3>
      <p id="confirmText">Are you sure you want to delete this section?</p>
      <div style="display:flex;justify-content:center;gap:10px;margin-top:10px;">
        <button id="confirmCancel" class="cancel-btn" type="button">Cancel</button>
        <button id="confirmDelete" class="add-btn" type="button" style="background:#b30000;">Delete</button>
      </div>
    </div>
  </div>
  <!-- The Modal -->
  <div id="sectionModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeModal">&times;</span>
      <h2>Add Section</h2>

      <form id="addSectionForm"> <!-- added id only, layout unchanged -->
        <label for="sectionName">Section Name</label>
        <input type="text" id="sectionName" name="sectionName" placeholder="Enter section name">

        <label for="gradeLevel">Grade Level</label>
        <select id="gradeLevel" name="gradeLevel">
          <option value="7">7</option>
          <option value="8">8</option>
          <option value="9">9</option>
          <option value="10">10</option>
        </select>

        <label for="sectionImage">Section Image</label>
        <input type="file" id="sectionImage" name="sectionImage" accept="image/*">

        <div class="btn-group">
          <button type="submit" class="add-btn">Add</button>
          <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
        </div>
      </form>
    </div>
    </div>
  <script>
    // Get elements
    const modal = document.getElementById("sectionModal");
    const openModalBtn = document.getElementById("openModal");
    const closeModal = document.getElementById("closeModal");
    const cancelBtn = document.getElementById("cancelBtn");

    // Open modal
    openModalBtn.onclick = () => {
      modal.style.display = "block";
    };

    // Close modal with X or Cancel
    closeModal.onclick = () => {
      modal.style.display = "none";
    };
    cancelBtn.onclick = () => {
      modal.style.display = "none";
    };

    // Close when clicking outside the modal
    window.onclick = (event) => {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    };
    
  
    // Delete behavior: open confirm modal, call deletesection.php on confirm, remove tile on success
    (function(){
      const container = document.querySelector('.container');
      const confirmModal = document.getElementById('confirmModal');
      const confirmText = document.getElementById('confirmText');
      const confirmCancel = document.getElementById('confirmCancel');
      const confirmDelete = document.getElementById('confirmDelete');

      let pendingId = null;
      let pendingTile = null;

      // delegate click on delete buttons
      container.addEventListener('click', (e) => {
        const btn = e.target.closest('.delete-btn');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        if (!id) return;
        pendingId = id;
        pendingTile = btn.closest('.rectangle');

        // show modal with contextual text
        confirmText.textContent = `Delete section "${pendingTile.querySelector('.rect-bottom').textContent.trim()}"? This cannot be undone.`;
        confirmModal.style.display = 'block';
      });

      confirmCancel.addEventListener('click', () => {
        pendingId = null;
        pendingTile = null;
        confirmModal.style.display = 'none';
      });

      // also close when clicking outside confirmModal
      window.addEventListener('click', (ev) => {
        if (ev.target === confirmModal) {
          pendingId = null;
          pendingTile = null;
          confirmModal.style.display = 'none';
        }
      });

      confirmDelete.addEventListener('click', async () => {
        if (!pendingId) return;
        try {
          const fd = new FormData();
          fd.append('id', pendingId);
          const res = await fetch('deletesection.php', { method: 'POST', body: fd });
          const text = await res.text();
          let data;
          try { data = JSON.parse(text); } catch (err) { data = { success:false, error: text }; }
          console.log('deletesection response:', res.status, text, data);
          if (data.success) {
            // remove DOM tile
            if (pendingTile && pendingTile.parentNode) pendingTile.parentNode.removeChild(pendingTile);
            pendingId = null;
            pendingTile = null;
            confirmModal.style.display = 'none';
          } else {
            alert(data.error || ('Failed to delete section. Server response: ' + (text || res.status)));
          }
        } catch (err) {
          console.error(err);
          alert('Network or server error while deleting. See console.');
        }
      });
    })();
  </script>
<script>
  (function(){
    const form = document.getElementById('addSectionForm');
    const container = document.querySelector('.container');
    const modal = document.getElementById('sectionModal');

    if (!form) return;

    function esc(s){ return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
      .replace(/'/g,'&#39;'); }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const name = document.getElementById('sectionName').value.trim();
      const grade = document.getElementById('gradeLevel').value;
      if (!name) { alert('Please enter a section name.'); return; }

      const fd = new FormData();
      fd.append('sectionName', name);
      fd.append('gradeLevel', grade);

      try {
        // include image file if selected
        const imgInput = document.getElementById('sectionImage');
        if (imgInput && imgInput.files && imgInput.files.length) {
          fd.append('sectionImage', imgInput.files[0]);
        }

        const res = await fetch('addsection.php', { method: 'POST', body: fd });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch (err) {
          console.error('Invalid JSON from addsection.php:', text);
          alert('Server returned invalid response. See console.');
          return;
        }

        if (!data.success) {
          alert(data.error || 'Failed to add section');
          return;
        }

        // Build new tile (match your layout)
        const wrapper = document.createElement('div');
        wrapper.className = 'rectangle';
        wrapper.setAttribute('data-id', data.id || '');
        wrapper.innerHTML = `
          <div class="rect-top"><img src="${esc(data.img || './images/peridot.jpg')}" alt="${esc(data.name)}"></div>
          <div class="rect-bottom">Grade ${esc(String(data.grade))} — ${esc(data.name)}
            <button class="delete-btn" data-id="${data.id}" title="Delete section">🗑</button>
          </div>
        `;

        // prepend so newest appears first (matches server order)
        container.prepend(wrapper);

        form.reset();
        modal.style.display = 'none';
      } catch (err) {
        console.error(err);
        alert('Network or server error. See console.');
      }
    });
  })();
</script>

</body>
</html>