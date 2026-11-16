$(document).ready(function() {
    // Notification function using bootstrap-notify
    function notificar(mensaje, tipo) {
        var config = {
            element: 'body',
            offset: 31,
            spacing: 10,
            placement: { from: "top", align: "center" },
            z_index: 10031
        };
        if (tipo === 'ok') {
            $.notify({
                icon: 'glyphicon glyphicon-ok-sign',
                message: mensaje
            }, $.extend(config, { type: 'success', delay: 1000 }));
        } else if (tipo === 'error') {
            $.notify({
                icon: 'glyphicon glyphicon-remove-sign',
                message: mensaje
            }, $.extend(config, { type: 'danger', delay: 2000 }));
        } else if (tipo === 'info') {
            $.notify({
                icon: 'glyphicon glyphicon-info-sign',
                message: mensaje
            }, $.extend(config, { type: 'info', delay: 2000 }));
        }
    }

    // Initialize DataTable
    var treload = $('#tabla-usuarios').DataTable({
        dom: 'T<"clear">lfrtip',
        tableTools: {
            sSwfPath: "plugins/DataTables/extensions/TableTools/swf/copy_csv_xls_pdf.swf",
            aButtons: [
                { sExtends: "copy", sButtonText: "Copy", mColumns: [0, 1, 2, 3, 4],
                    fnComplete: function() {
                        this.fnInfo('<h3>Copied Table</h3><p>Use Ctrl + v to paste the copied.</p>', 2500);
                    }
                },
                { sExtends: "csv", sButtonText: "CSV", mColumns: [0, 1, 2, 3, 4] },
                { sExtends: "xls", sButtonText: "Excel", mColumns: [0, 1, 2, 3, 4] },
                { sExtends: "pdf", sButtonText: "PDF", mColumns: [0, 1, 2, 3, 4] },
                { sExtends: "print", sButtonText: "Print",
                    sInfo: "<h2>Print View</h2><br>Use the print function of your browser or Ctrl + P. Press escape to exit."
                }
            ]
        },
        oLanguage: { sUrl: "plugins/dataTables/dataTables.spanish.txt" },
        columnDefs: [{
            targets: 5,
            data: "id",
            render: function(data, type, full) {
                return `
                    <button type="button" class="btn btn-sm btn-default editar" id="${full['id']}" data-toggle="modal" data-target="#editar"><i class="fa fa-pencil"></i></button>
                    <button type="button" class="btn btn-sm btn-default estado" id="${full['id']}"><i class="fa fa-shield"></i></button>
                    <button type="button" class="btn btn-sm btn-default eliminar" id="${full['id']}"><i class="fa fa-trash"></i></button>
                `;
            }
        }],
        ajax: {
            url: "settings/modebookings/listar.php",
            type: "GET",
            dataSrc: "",
            error: function(xhr, error, thrown) {
                notificar("Failed to load table data: " + thrown, "error");
            }
        },
        columns: [
            { data: 'name' },
            { data: 'services' },
            { data: 'deliverytime' },
            { data: 'observations' },
            {
                data: 'estado',
                render: function(data) {
                    return data == 1 ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
                }
            }
        ]
    });

    // Style export buttons
    var tt = new $.fn.dataTable.TableTools(treload);
    $(tt.fnContainer()).insertBefore('div.dataTables_wrapper');

    // Reset and focus on new modal
    $('#nuevo').on('shown.bs.modal', function() {
        $('#formularioNuevo')[0].reset();
        $('#formularioNuevo input[name="name"]').focus();
    });

    // Focus on edit modal
    $('#editar').on('shown.bs.modal', function() {
        $('#formularioEditar input[name="name"]').focus();
    });

    // Reload table
    $('#recarga').click(function() {
        treload.ajax.reload();
    });

    // Add new shipping mode
    $('#guardarNuevo').click(function() {
        var datos = $('#formularioNuevo').serialize();
        $.ajax({
            type: "POST",
            url: "settings/modebookings/agregar.php",
            data: datos,
            dataType: "json",
            success: function(data) {
                switch (data.msg) {
                    case 'ok':
                        $('#nuevo').modal('toggle');
                        notificar("The register was successfully registered in the database.", "ok");
                        treload.ajax.reload();
                        break;
                    case 'nomvacio':
                        notificar("Error: The name field is required.", "error");
                        $('#gnombre').addClass('has-error');
                        break;
                    case 'apevacio':
                        notificar("Error: The services field is required.", "error");
                        $('#gapellido').addClass('has-error');
                        break;
                    case 'telvacio':
                        notificar("Error: The delivery time field is required.", "error");
                        $('#gtelefono').addClass('has-error');
                        break;
                    case 'emavacio':
                        notificar("Error: The observations field is required.", "error");
                        $('#gemail').addClass('has-error');
                        break;
                    default:
                        notificar(data.details || "Failed to add shipping mode.", "error");
                }
            },
            error: function(xhr, status, error) {
                notificar("Failed to add shipping mode: " + error, "error");
            }
        });
    });

    // Update shipping mode
    $('#actualizar').click(function() {
        var datos = $('#formularioEditar').serialize();
        $.ajax({
            type: "POST",
            url: "settings/modebookings/actualizar.php",
            data: datos,
            dataType: "json",
            success: function(data) {
                if (data.msg === 'ok') {
                    $('#editar').modal('toggle');
                    notificar("Register data were updated correctly.", "info");
                    treload.ajax.reload();
                } else {
                    switch (data.msg) {
                        case 'nomvacio':
                            notificar("Error: The name field is required.", "error");
                            $('#Enombre').addClass('has-error');
                            break;
                        case 'apevacio':
                            notificar("Error: The services field is required.", "error");
                            $('#Eapellido').addClass('has-error');
                            break;
                        case 'telvacio':
                            notificar("Error: The delivery time field is required.", "error");
                            $('#Etelefono').addClass('has-error');
                            break;
                        case 'emavacio':
                            notificar("Error: The observations field is required.", "error");
                            $('#Eemail').addClass('has-error');
                            break;
                        default:
                            notificar(data.details || "Failed to update shipping mode.", "error");
                    }
                }
            },
            error: function(xhr, status, error) {
                notificar("Failed to update shipping mode: " + error, "error");
            }
        });
    });

    // Delete shipping mode
    $(document).on("click", '.eliminar', function() {
        var id = $(this).attr("id");
        swal({
            title: "Are you sure?",
            text: "This register will be completely removed from the database",
            type: "warning",
            showCancelButton: true,
            cancelButtonText: "Cancel",
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, Delete",
            closeOnConfirm: false
        }, function() {
            $.ajax({
                type: "POST",
                url: "settings/modebookings/eliminar.php",
                data: { id: id },
                dataType: "json",
                success: function(data) {
                    if (data.msg === 'ok') {
                        swal("Removed!", "The register was removed from the Database", "success");
                        treload.ajax.reload();
                    } else {
                        notificar(data.details || "Error: Could not delete the bookings", "error");
                    }
                },
                error: function(xhr, status, error) {
                    notificar("Failed to delete shipping mode: " + error, "error");
                }
            });
        });
    });

    // Toggle status
    $(document).on("click", '.estado', function() {
        var id = $(this).attr("id");
        swal({
            title: "Are you sure?",
            text: "It will change the register status",
            type: "warning",
            showCancelButton: true,
            cancelButtonText: "Cancel",
            confirmButtonColor: "#5BC0DE",
            confirmButtonText: "Yes, Change",
            closeOnConfirm: false
        }, function() {
            $.ajax({
                type: "POST",
                url: "settings/modebookings/estado.php",
                data: { id: id },
                dataType: "json",
                success: function(data) {
                    if (data.msg === 'ok') {
                        swal("Changed", "It is to change the register status.", "success");
                        treload.ajax.reload();
                    } else {
                        notificar(data.details || "Error: Failed to change the status of the register", "error");
                    }
                },
                error: function(xhr, status, error) {
                    notificar("Failed to change status: " + error, "error");
                }
            });
        });
    });

    // Fetch data for edit
    $(document).on("click", '.editar', function() {
        var id = $(this).attr("id");
        $('#id_user').val(id);
        $.ajax({
            type: "POST",
            url: "settings/modebookings/editar.php",
            data: { id: id },
            dataType: 'json',
            success: function(data) {
                if (data.length > 0) {
                    $('#formularioEditar input[name="id"]').val(data[0].id);
                    $('#formularioEditar input[name="name"]').val(data[0].name);
                    $('#formularioEditar input[name="services"]').val(data[0].services);
                    $('#formularioEditar input[name="deliverytime"]').val(data[0].deliverytime);
                    $('#formularioEditar input[name="observations"]').val(data[0].observations);
                    $('#formularioEditar input[name="estado"]').prop('checked', data[0].estado == 1);
                } else {
                    notificar("Error: Shipping mode not found", "error");
                }
            },
            error: function(xhr, status, error) {
                notificar("Failed to fetch shipping mode: " + error, "error");
            }
        });
    });

    // Form validation for new modal
    $('#formularioNuevo').on('input', '.name, .services, .deliverytime, .observations', function() {
        var group = $(this).closest('.form-group');
        group.toggleClass('has-error', $(this).val().length <= 1);
    });

    // Form validation for edit modal
    $('#formularioEditar').on('input', '.name, .services, .deliverytime, .observations', function() {
        var group = $(this).closest('.form-group');
        group.toggleClass('has-error', $(this).val().length <= 1);
    });
});