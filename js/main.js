jQuery(function ($) {
  // multiple select with AJAX search
  $("#paginas_campanha").select2();

  function retrieve_data(campanha = "", start = "", end = "") {
    // console.log("id campanha", campanha);
    var query_data = {
      action: "get_ab_test_report_data",
      campanha: campanha,
      start: start,
      end: end
    };

    

    $.post(ajaxurl, query_data, function (response) {
      // return response;
      if (response) {
        //   console.log(response);
        results = [...JSON.parse(response)];

        // console.log(results);
        $("#report_list").DataTable({
          destroy: true,
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
              targets: [3, 8],
              data: "creation_time",
              render: function (data, type, row, meta) {
                if (!data) return "--";
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
            { data: "page_is" },
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

        conversoes_full = $.grep(results, function (n, i) {
          return n.origin_ip !== null && n.return_ip !== null;
        });
        $(".conversoes_full .result").html(conversoes_full.length);
        // antes contavam conversoes sem cookies
        // conversoes_partial = jQuery.grep(results, function (n, i) {
        //   return n.origin_ip === null && n.return_ip !== null;
        // });

        conversoes_partial = +((conversoes_full.length/acessos)*100).toFixed(2);
        resultado_porcento = (conversoes_partial > 0) ? (conversoes_partial) : (0);
        $(".conversoes_partial .result").html(resultado_porcento+"%");
      }
    });
  }
  function retrieve_page_data(campanha = "", start = "", end = "") {
    // console.log("id campanha", campanha);
    var query_data = {
      action: "get_ab_page_report_data",
      campanha: campanha,
      start: start,
      end: end
    };

    $.post(ajaxurl, query_data, function (response) {
      // return response;
      if (response) {
        // console.log(response);
        results = [...JSON.parse(response)];

        // console.log(results);
        $(".resultados_paginas .result").empty();
        results.map((value, index) => {

          let porcentagem = parseFloat(value.porcentagem).toFixed(2);
          // porcentagem = parseInt(porcentagem).toFixed(2)

          $(".resultados_paginas .result").append(`
              <tr>
                  <th scope="row">${value.pagina}</th>
                  <td>${value.post_title}</td>
                  <td>${value.acessos}</td>
                  <td>${value.conversoes}</td>
                  <td>${porcentagem}%</td>
              </tr>
          `);
        });

        //
      }
    });
  }
  if ($("#reports").length) {
    retrieve_data();
    retrieve_page_data();

    $("#ab_filter_data, #startDate, #endDate").on("change", function () {
      $("#report_list").DataTable().clear();
      $("#report_list").DataTable().destroy();

      retrieve_data($('#ab_filter_data').val(), $('#startDate').val(), $('#endDate').val());
      retrieve_page_data($('#ab_filter_data').val(), $('#startDate').val(), $('#endDate').val());
    });
  }
  if ($(".zerador").length){
    $(".zerador").on("click", function () {
      let confirmation = prompt("Tem certeza que quer apagar os dados desta campanha? Para confirmar digite: "+ this.value);

      if(confirmation === this.value){
        var query_data = {
          action: "delete_report_data",
          campanha: this.value,
        };
    
        $.post(ajaxurl, query_data, function (response) {
          // return response;
          if (response) {
            console.log(response);
            alert(response + " registros apagados");
            // results = [...JSON.parse(response)];

            
            //
          }
        });
        
      }
    });
  }
  if ($(".corretor").length){
    $(".corretor").on("click", function () {
      var query_data = {
        action: "fix_report_data",
        campanha: this.value,
      };
  
      $.post(ajaxurl, query_data, function (response) {
        // return response;
        if (response) {
          console.log(response);
          //alert(response + " registros apagados");
          // results = [...JSON.parse(response)];

          
          //
        }
      });
    });
  }
});
