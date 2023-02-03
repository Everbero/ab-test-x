jQuery(function ($) {
  // multiple select with AJAX search
  $("#paginas_campanha").select2();

  function retrieve_data(campanha = "") {
    console.log("id campanha", campanha);
    var query_data = {
      action: "get_ab_test_report_data",
      campanha: campanha,
    };

    jQuery.post(ajaxurl, query_data, function (response) {
      // return response;
      if (response) {
        //   console.log(response);
        results = [...JSON.parse(response)];

        console.log(results);
        $("#report_list").DataTable({
          scrollX: true,
          responsive: true,
          language: {
            url: "https://cdn.datatables.net/plug-ins/1.12.1/i18n/pt-BR.json",
          },
          columnDefs: [
            {
              target: 1,
              visible: false,
              searchable: false,
            },
            {
              targets: [3,8],
              data: "creation_time",
              render: function (data, type, row, meta) {
                if (!data) return '--';
                return new Date(data).toLocaleDateString("pt-br", {
                  weekday: "long",
                  year: "numeric",
                  month: "short",
                  day: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
                  second: "2-digit",
                });
              },
            },
          ],
          paging: true,
          data: results,
          columns: [
            { data: "post_title" },
            { data: "cookie_hash" },
            { data: "origin_ip" },
            { data: "creation_time" },
            { data: "page" },
            { data: "params" },
            { data: "destination" },
            { data: "return_ip" },
            { data: "return_time" },
          ],
        });

        //dados para os cards
        let acessos, conversoes_full, conversoes_partial;

        acessos = results.length;
        $(".acessos .result").html(acessos);

        conversoes_full = jQuery.grep(results, function (n, i) {
          return n.origin_ip !== null && n.return_ip !== null;
        });
        $(".conversoes_full .result").html(conversoes_full.length);

        conversoes_partial = jQuery.grep(results, function (n, i) {
          return n.origin_ip === null && n.return_ip !== null;
        });
        $(".conversoes_partial .result").html(conversoes_partial.length);
      }
    });
  }
  if ($("#reports").length) {
    retrieve_data();

    $("#ab_filter_data").on("change", function () {
      $("#report_list").DataTable().clear();
      $("#report_list").DataTable().destroy();

      retrieve_data(this.value);
    });
  }
});
