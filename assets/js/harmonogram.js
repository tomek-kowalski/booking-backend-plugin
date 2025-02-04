jQuery(document).ready(function ($) {
    function fetchNewRecords() {
        let currentPage = new URLSearchParams(window.location.search).get("paged") || 1;

        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "fetch_harmonogram",
                paged: currentPage
            },
            success: function (response) {
                $("#harmonogram-table tbody").html(response);
            }
        });
    }

    setInterval(fetchNewRecords, 2000);

    console.log('text');

    $(document).on("click", ".delete-row", function () {
        const $button = $(this);
        const recordId = $button.data("id");
    
        if (confirm("Czy na pewno chcesz skasować rezerwację?")) {
            $button.prop("disabled", true).text("Usuwanie...");
    
            $.ajax({
                url: ajaxurl,
                method: "POST",
                data: {
                    action: "delete_row",
                    id: recordId
                },
                success: function (response) {
                    console.log("Server response:", response);
                    if (response.success) {
                        alert("Rezerwacja usunięta.");
                        fetchNewRecords();
                    } else {
                        alert(response.data?.message || "Nie udało się usunąć rezerwacji.");
                    }
                },
                error: function () {
                    alert("Wystąpił błąd podczas usuwania rezerwacji.");
                },
                complete: function () {
                    $button.prop("disabled", false).text("Usuń");
                }
            });
        }
    });
    

    $(document).on("click", ".change-status", function () {
        const recordId = $(this).data("id");
        const statusCell = $(this).closest("tr").find(".status-cell");
        const statusButton = $(this);
    
        $.ajax({
            url: ajaxurl,
            method: "POST",
            data: {
                action: "change_status",
                id: recordId
            },
            success: function (response) {
                if (response.success) {
                    let newStatus = response.new_status;
                    statusCell.text(newStatus);
                    
                    statusButton.text(newStatus);
                    
                    if (newStatus === "Zakończone") {
                        statusButton.prop("disabled", true);
                    }
                } else {
                    alert("Coś nie zadziałało.");
                }
            },
            error: function () {
                alert("An error occurred while updating the status.");
            }
        });
    });
    
});
