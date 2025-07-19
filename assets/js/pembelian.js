/**
 * Transaksi Pembelian - YMC Cashier
 * JavaScript for handling purchase transactions
 */

document.addEventListener("DOMContentLoaded", function () {
  // DOM elements
  const elements = {
    addModal: document.getElementById("addPembelianModal"),
    editModal: document.getElementById("editPembelianModal"),
    invoiceModal: document.getElementById("invoiceModal"),
    deleteModal: document.getElementById("deleteConfirmModal"),
    openAddModalBtn: document.getElementById("openAddModal"),
    closeButtons: document.querySelectorAll(".close, .closeModal"),
    addItemBtn: document.getElementById("addItemBtn"),
    listItems: document.getElementById("listItems"),
    totalHargaDisplay: document.getElementById("totalHarga"),
    addPembelianForm: document.getElementById("addPembelianForm"),
    editPembelianForm: document.getElementById("editPembelianForm"),
    barangSelect: document.getElementById("id_barang"),
    hargaInput: document.getElementById("harga_beli"),
    jumlahInput: document.getElementById("jumlah"),
    printTableBtn: document.getElementById("printTableBtn"),
    printInvoiceBtn: document.getElementById("printInvoiceBtn"),
    searchInput: document.getElementById("search"),
    pemasokFilter: document.getElementById("pemasok"),
    tanggalAwalInput: document.getElementById("tanggal_awal"),
    tanggalAkhirInput: document.getElementById("tanggal_akhir"),
    editButtons: document.querySelectorAll(".edit-pembelian"),
    viewButtons: document.querySelectorAll(".view-pembelian"),
    confirmDeleteBtn: document.getElementById("confirmDeleteBtn"),
  };

  // State variables
  const state = {
    items: [],
    totalHarga: 0,
    currentPembelianId: null,
  };

  // ----- Helper Functions -----

  /**
   * Format number as Indonesian Rupiah
   */
  function formatRupiah(angka) {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }).format(angka);
  }

  /**
   * Show a modal dialog
   */
  function showModal(modal) {
    if (modal) modal.style.display = "block";
  }

  /**
   * Hide a modal dialog
   */
  function hideModal(modal) {
    if (modal) modal.style.display = "none";
  }

  /**
   * Handle API request errors
   */
  function handleApiError(error, customMessage) {
    console.error("Error:", error);
    alert(customMessage || "Terjadi kesalahan saat memproses data");
  }

  // ----- UI Functions -----

  /**
   * Update the items list display
   */
  function updateItemsList() {
    if (state.items.length === 0) {
      elements.listItems.innerHTML =
        '<div class="text-sm text-gray-500 italic text-center p-4">Belum ada item yang ditambahkan</div>';
      state.totalHarga = 0;
    } else {
      state.totalHarga = 0;
      elements.listItems.innerHTML = "";

      state.items.forEach((item, index) => {
        state.totalHarga += item.subtotal;

        const itemElement = document.createElement("div");
        itemElement.className =
          "flex justify-between items-center p-2 border-b";
        itemElement.innerHTML = `
                    <div class="flex-1">
                        <span class="font-medium">${item.nama_barang}</span>
                        <div class="text-sm text-gray-600">${
                          item.jumlah
                        } x ${formatRupiah(item.harga_beli)}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">${formatRupiah(
                          item.subtotal
                        )}</div>
                        <button type="button" class="text-red-500 hover:text-red-700 text-sm remove-item" data-index="${index}">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                `;

        elements.listItems.appendChild(itemElement);
      });

      // Add event listeners to remove buttons
      document.querySelectorAll(".remove-item").forEach((button) => {
        button.addEventListener("click", function () {
          const index = parseInt(this.dataset.index);
          state.items.splice(index, 1);
          updateItemsList();
        });
      });
    }

    elements.totalHargaDisplay.textContent = formatRupiah(state.totalHarga);
  }

  /**
   * Update subtotal when quantity changes in edit mode
   */
  function updateEditItemSubtotal() {
    const itemId = this.dataset.id;
    const quantity = parseInt(this.value) || 0;
    const priceText = this.nextElementSibling.textContent.replace(/[^\d]/g, "");
    const price = parseInt(priceText) || 0;

    const subtotal = quantity * price;
    const subtotalElement = document.querySelector(
      `.item-subtotal[data-id="${itemId}"]`
    );
    subtotalElement.textContent = formatRupiah(subtotal);

    updateEditTotalAmount();
  }

  /**
   * Remove item in edit mode
   */
  function removeEditItem() {
    const itemId = this.dataset.id;
    const itemElement = this.closest(".flex.justify-between");
    itemElement.style.display = "none";
    itemElement.dataset.removed = "true";

    updateEditTotalAmount();
  }

  /**
   * Update total amount in edit mode
   */
  function updateEditTotalAmount() {
    let total = 0;

    document.querySelectorAll(".item-subtotal").forEach((item) => {
      const itemElement = item.closest(".flex.justify-between");
      if (itemElement && itemElement.dataset.removed !== "true") {
        const subtotalText = item.textContent.replace(/[^\d]/g, "");
        const subtotal = parseInt(subtotalText) || 0;
        total += subtotal;
      }
    });

    document.getElementById("editTotalHarga").textContent = formatRupiah(total);
  }

  /**
   * Apply date filter
   */
  function applyDateFilter() {
    const startDate = elements.tanggalAwalInput.value;
    const endDate = elements.tanggalAkhirInput.value;

    if (!startDate && !endDate) {
      return;
    }

    const rows = document.querySelectorAll("#pembelianTableBody tr");
    const start = startDate ? new Date(startDate) : new Date(0);
    const end = endDate ? new Date(endDate) : new Date(8640000000000000);

    rows.forEach((row) => {
      const dateCell = row.querySelector("td:nth-child(3)").textContent;
      const parts = dateCell.split("/");
      const rowDate = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);

      if (rowDate >= start && rowDate <= end) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  }

  // ----- AJAX Functions -----

  /**
   * Fetch pembelian details
   */
  function fetchPembelianDetails(id, successCallback) {
    fetch(`ajax/get_pembelian.php?id=${id}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          successCallback(data);
        } else {
          alert("Error: " + data.message);
        }
      })
      .catch((error) =>
        handleApiError(error, "Terjadi kesalahan saat mengambil data")
      );
  }

  /**
   * Save new pembelian
   */
  function savePembelian(formData) {
    fetch("ajax/save_pembelian.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(formData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Pembelian berhasil disimpan!");
          hideModal(elements.addModal);
          location.reload();
        } else {
          alert("Error: " + data.message);
        }
      })
      .catch((error) =>
        handleApiError(error, "Terjadi kesalahan saat menyimpan data")
      );
  }

  /**
   * Update existing pembelian
   */
  function updatePembelian(formData) {
    fetch("ajax/update_pembelian.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(formData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Pembelian berhasil diperbarui!");
          hideModal(elements.editModal);
          location.reload();
        } else {
          alert("Error: " + data.message);
        }
      })
      .catch((error) =>
        handleApiError(error, "Terjadi kesalahan saat memperbarui data")
      );
  }

  /**
   * Delete pembelian
   */
  function deletePembelian(id) {
    fetch(`ajax/delete_pembelian.php?id=${id}`, {
      method: "DELETE",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Pembelian berhasil dihapus!");
          hideModal(elements.deleteModal);
          location.reload();
        } else {
          alert("Error: " + data.message);
        }
      })
      .catch((error) =>
        handleApiError(error, "Terjadi kesalahan saat menghapus data")
      );
  }

  /**
   * Generate invoice HTML
   */
  function generateInvoiceHtml(pembelian, items) {
    let itemsHtml = "";
    let totalHarga = 0;

    items.forEach((item) => {
      totalHarga += parseFloat(item.subtotal);
      itemsHtml += `
                <tr>
                    <td class="px-4 py-2 border">${item.nama_barang}</td>
                    <td class="px-4 py-2 border text-right">${item.jumlah}</td>
                    <td class="px-4 py-2 border text-right">${formatRupiah(
                      item.harga_beli
                    )}</td>
                    <td class="px-4 py-2 border text-right">${formatRupiah(
                      item.subtotal
                    )}</td>
                </tr>
            `;
    });

    return `
            <div class="p-4">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold">INVOICE PEMBELIAN</h2>
                    <p class="text-gray-600">YMC - Yumna Moslem Collection</p>
                </div>
                
                <div class="flex justify-between mb-6">
                    <div>
                        <p class="font-bold">Pemasok:</p>
                        <p>${pembelian.nama_pemasok}</p>
                    </div>
                    <div class="text-right">
                        <p><strong>No. Invoice:</strong> INV-P-${
                          pembelian.id_pembelian
                        }</p>
                        <p><strong>Tanggal:</strong> ${new Date(
                          pembelian.tanggal
                        ).toLocaleDateString("id-ID")}</p>
                    </div>
                </div>
                
                <table class="w-full border-collapse border mb-6">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 border text-left">Item</th>
                            <th class="px-4 py-2 border text-right">Jumlah</th>
                            <th class="px-4 py-2 border text-right">Harga Satuan</th>
                            <th class="px-4 py-2 border text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td colspan="3" class="px-4 py-2 border text-right">Total</td>
                            <td class="px-4 py-2 border text-right">${formatRupiah(
                              totalHarga
                            )}</td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="mt-8 pt-4 border-t">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold mb-2">Catatan:</p>
                            <p class="text-gray-600">Terima kasih atas kerjasamanya.</p>
                        </div>
                        <div class="text-center">
                            <p class="mb-12">Hormat Kami,</p>
                            <p class="font-bold">YMC - Yumna Moslem Collection</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
  }

  // ----- Event Handlers -----

  // Toggle filter dropdown
  document
    .getElementById("filterButton")
    .addEventListener("click", function () {
      document.getElementById("filterContent").classList.toggle("show");
    });

  // Close dropdown if clicked outside
  window.addEventListener("click", function (event) {
    if (
      !event.target.matches(".filter-button") &&
      !event.target.closest(".filter-content")
    ) {
      const dropdown = document.getElementById("filterContent");
      if (dropdown.classList.contains("show")) {
        dropdown.classList.remove("show");
      }
    }
  });

  // Open add modal
  if (elements.openAddModalBtn) {
    elements.openAddModalBtn.addEventListener("click", function () {
      state.items = [];
      state.totalHarga = 0;
      updateItemsList();
      showModal(elements.addModal);
    });
  }

  // Close modals
  elements.closeButtons.forEach(function (btn) {
    btn.addEventListener("click", function () {
      hideModal(elements.addModal);
      hideModal(elements.editModal);
      hideModal(elements.invoiceModal);
      hideModal(elements.deleteModal);
    });
  });

  // When clicking outside modal, close it
  window.addEventListener("click", function (event) {
    if (event.target === elements.addModal) hideModal(elements.addModal);
    if (event.target === elements.editModal) hideModal(elements.editModal);
    if (event.target === elements.invoiceModal)
      hideModal(elements.invoiceModal);
    if (event.target === elements.deleteModal) hideModal(elements.deleteModal);
  });

  // Update harga input when selecting barang
  elements.barangSelect.addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
      elements.hargaInput.value = selectedOption.dataset.harga;
    } else {
      elements.hargaInput.value = "";
    }
  });

  // Add item to list
  elements.addItemBtn.addEventListener("click", function () {
    const barangId = elements.barangSelect.value;
    if (!barangId) {
      alert("Silakan pilih barang terlebih dahulu");
      return;
    }

    const selectedOption =
      elements.barangSelect.options[elements.barangSelect.selectedIndex];
    const barangNama = selectedOption.dataset.nama;
    const jumlah = parseInt(elements.jumlahInput.value) || 0;
    const harga = parseFloat(elements.hargaInput.value) || 0;
    const subtotal = jumlah * harga;

    if (jumlah <= 0) {
      alert("Jumlah harus lebih dari 0");
      return;
    }

    if (harga <= 0) {
      alert("Harga harus lebih dari 0");
      return;
    }

    // Check if item already exists
    const existingItemIndex = state.items.findIndex(
      (item) => item.id_barang === barangId
    );
    if (existingItemIndex !== -1) {
      // Update existing item
      state.items[existingItemIndex].jumlah += jumlah;
      state.items[existingItemIndex].subtotal =
        state.items[existingItemIndex].jumlah *
        state.items[existingItemIndex].harga_beli;
    } else {
      // Add new item
      state.items.push({
        id_barang: barangId,
        nama_barang: barangNama,
        jumlah: jumlah,
        harga_beli: harga,
        subtotal: subtotal,
      });
    }

    updateItemsList();

    // Reset inputs
    elements.barangSelect.selectedIndex = 0;
    elements.jumlahInput.value = 1;
    elements.hargaInput.value = "";
  });

  // Submit add form
  elements.addPembelianForm.addEventListener("submit", function (e) {
    e.preventDefault();

    if (state.items.length === 0) {
      alert("Silakan tambahkan minimal 1 item barang");
      return;
    }

    const formData = {
      tanggal: document.getElementById("tanggal").value,
      id_pemasok: document.getElementById("id_pemasok").value,
      items: state.items,
      total_harga_beli: state.totalHarga,
    };

    savePembelian(formData);
  });

  // Edit pembelian
  elements.editButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.closest("button").dataset.id;
      state.currentPembelianId = id;

      fetchPembelianDetails(id, function (data) {
        document.getElementById("edit_id_pembelian").value =
          data.pembelian.id_pembelian;
        document.getElementById("edit_tanggal").value = data.pembelian.tanggal;
        document.getElementById("edit_id_pemasok").value =
          data.pembelian.id_pemasok;

        // Load items
        let itemsHtml = "";
        let totalHarga = 0;

        data.items.forEach((item, index) => {
          totalHarga += parseFloat(item.subtotal);
          itemsHtml += `
                        <div class="flex justify-between items-center p-2 border-b">
                            <div class="flex-1">
                                <span class="font-medium">${
                                  item.nama_barang
                                }</span>
                                <div class="flex items-center mt-1">
                                    <input type="number" class="edit-item-qty w-16 px-2 py-1 border rounded mr-2"
                                        value="${
                                          item.jumlah
                                        }" min="1" max="999" data-id="${
            item.id_detail_pembelian
          }">
                                    <span class="text-gray-600">x ${formatRupiah(
                                      item.harga_beli
                                    )}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium item-subtotal" data-id="${
                                  item.id_detail_pembelian
                                }">
                                    ${formatRupiah(item.subtotal)}
                                </div>
                                <button type="button" class="text-red-500 hover:text-red-700 text-sm remove-edit-item" data-id="${
                                  item.id_detail_pembelian
                                }">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </div>
                        </div>
                    `;
        });

        itemsHtml += `
                    <div class="mt-4 pt-4 border-t flex justify-between items-center">
                        <span class="font-semibold text-gray-700">Total:</span>
                        <span id="editTotalHarga" class="font-bold text-lg">${formatRupiah(
                          totalHarga
                        )}</span>
                    </div>
                `;

        document.getElementById("editItemsList").innerHTML = itemsHtml;

        // Add event listeners to quantity inputs and remove buttons
        document.querySelectorAll(".edit-item-qty").forEach((input) => {
          input.addEventListener("change", updateEditItemSubtotal);
        });

        document.querySelectorAll(".remove-edit-item").forEach((button) => {
          button.addEventListener("click", removeEditItem);
        });

        showModal(elements.editModal);
      });
    });
  });

  // Submit edit form
  elements.editPembelianForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const updatedItems = [];
    const removedItems = [];

    document.querySelectorAll(".edit-item-qty").forEach((input) => {
      const itemElement = input.closest(".flex.justify-between");
      const itemId = input.dataset.id;

      if (itemElement.dataset.removed === "true") {
        removedItems.push(itemId);
      } else {
        updatedItems.push({
          id_detail_pembelian: itemId,
          jumlah: parseInt(input.value) || 0,
        });
      }
    });

    const formData = {
      id_pembelian: document.getElementById("edit_id_pembelian").value,
      tanggal: document.getElementById("edit_tanggal").value,
      id_pemasok: document.getElementById("edit_id_pemasok").value,
      updated_items: updatedItems,
      removed_items: removedItems,
    };

    updatePembelian(formData);
  });

  // View invoice
  elements.viewButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.closest("button").dataset.id;

      fetchPembelianDetails(id, function (data) {
        const invoiceHtml = generateInvoiceHtml(data.pembelian, data.items);
        document.getElementById("invoice-container").innerHTML = invoiceHtml;
        showModal(elements.invoiceModal);
      });
    });
  });

  /**
   * Handle print table functionality
   */
  function handlePrintTable() {
    const printWindow = window.open("", "", "height=600,width=800");
    const table = document.getElementById("pembelianTable").cloneNode(true);

    // Remove action column and buttons
    const actionColumnIndex = [...table.rows[0].cells].findIndex(
      (cell) => cell.textContent.trim() === "Aksi"
    );
    if (actionColumnIndex !== -1) {
      [...table.rows].forEach((row) => {
        if (row.cells[actionColumnIndex]) {
          row.deleteCell(actionColumnIndex);
        }
      });
    }

    const html = `
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Laporan Transaksi Pembelian</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 2cm;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 18px;
                    font-weight: bold;
                }
                .header p {
                    margin: 5px 0;
                    font-size: 14px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 8px;
                    font-size: 12px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .footer {
                    margin-top: 30px;
                    text-align: right;
                    font-size: 12px;
                }
                @media print {
                    @page {
                        margin: 2cm;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>LAPORAN TRANSAKSI PEMBELIAN</h1>
                <p>YMC - Yumna Moslem Collection</p>
                <p>Tanggal Cetak: ${new Date().toLocaleDateString("id-ID", {
                  day: "numeric",
                  month: "long",
                  year: "numeric",
                })}</p>
            </div>
            ${table.outerHTML}
            <div class="footer">
                <p>Dicetak oleh: ${
                  config.userLevel.charAt(0).toUpperCase() +
                  config.userLevel.slice(1)
                }</p>
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(html);
    printWindow.document.close();

    // Wait for window to load before printing
    printWindow.onload = function () {
      printWindow.print();
      // Close window after printing (optional - comment out if you want to keep it open)
      printWindow.onafterprint = function () {
        printWindow.close();
      };
    };
  }

  // Update the event listener for print button
  document
    .getElementById("printTableBtn")
    .addEventListener("click", handlePrintTable);

  // Print invoice
  elements.printInvoiceBtn.addEventListener("click", function () {
    window.print();
  });

  // Client-side search functionality
  elements.searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll("#pembelianTableBody tr");

    rows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      if (text.includes(searchTerm)) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  });

  // Filter by pemasok
  elements.pemasokFilter.addEventListener("change", function () {
    const pemasokId = this.value;
    const rows = document.querySelectorAll("#pembelianTableBody tr");

    if (!pemasokId) {
      // Show all rows if no pemasok is selected
      rows.forEach((row) => {
        row.style.display = "";
      });
      return;
    }

    // Otherwise, filter by pemasok ID
    rows.forEach((row) => {
      // Using data-pemasok-id attribute to filter
      const rowPemasokId = row.getAttribute("data-pemasok-id");
      if (rowPemasokId === pemasokId) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  });

  // Date range filter
  elements.tanggalAwalInput.addEventListener("change", applyDateFilter);
  elements.tanggalAkhirInput.addEventListener("change", applyDateFilter);
});
