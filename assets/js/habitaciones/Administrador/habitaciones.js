 // Rutas de la pagina
 router.on({
     // Ver datos de paciente
     'ver-paciente/:id': function(params) {
         $('#card').html(template($('#ver-paciente').html(), {
             id: params.id
         }));
     },
     // todas las habitaciones y estados
     '*': function() {
         table();
     }
 }).resolve();
 // Updated 28 October 2011: Now allows 0, NaN, false, null and undefined in output. 
 function template(templateid, data) {
     return templateid.replace(/%(\w*)%/g, // or /{(\w*)}/g for "{this} instead of %this%"
         function(m, key) {
             return data.hasOwnProperty(key) ? data[key] : "";
         });
 }

 function MaysPrimera(string) {
     return string.charAt(0).toUpperCase() + string.slice(1);
 }

 function table() {
     var table = $('#habitaciones').DataTable({
             "ajax": {
                 url: "api/habitaciones",
                 dataSrc: "customData",
                 serverSide: true,
             },
             cache: false,
             columns: [{
                 title: "N°:"
             }, {
                 title: "Habitación:"
             }, {
                 title: "Paciente:"
             }, {
                 title: "Status:"
             }, {
                 title: "Dias de Hosp.:"
             }, {
                 title: "Ultima Interacción:"
             }, {
                 title: "Opciones:"
             }, ],
             aoColumnDefs: [{
                 mRender: function(data, type, row, meta) {
                     return meta.row + meta.settings._iDisplayStart + 1;
                 },
                 visible: true,
                 width: '40px',
                 aTargets: [0]
             }, {
                 mRender: function(data, type, full) {
                     return full.hab;
                 },
                 visible: true,
                 aTargets: [1]
             }, {
                 mRender: function(data, type, full) {
                     return full.status;
                 },
                 visible: true,
                 aTargets: [2]
             }, {
                 mRender: function(data, type, full) {
                     return full.pte;
                 },
                 visible: true,
                 aTargets: [3]
             }, {
                 mRender: function(data, type, full) {
                     return full.id;
                 },
                 visible: true,
                 aTargets: [4]
             }, {
                 mRender: function(data, type, full) {
                     return '<button class="btn btn-outline-primary btn-xs editpte" type="button"  id="' + full.id + '">Oh Hi, Folks!</button>';
                 },
                 visible: true,
                 aTargets: [5]
             }, {
                 mRender: function(data, type, full) {
                     return template($('#btn-table-opc').html(), {
                         id: full.id
                     });
                 },
                 visible: true,
                 aTargets: [6]
             }, ],
             "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {},
             "drawCallback": function(settings) {
                 $('.view').click(function(e) {
                     e.preventDefault();
                     router.navigate('/ver-paciente/' + $(this).attr('id'));
                 });
             },
             "order": [
                 [0, 'Desc']
             ],
             "language": {
                 "url": "assets/js/spanish.json"
             },
             rowId: 'id',
             liveAjax: {
                 // 2 second interval
                 interval: 1100,
                 // Do _not_ fire the DT callbacks for every XHR request made by liveAjax
                 dtCallbacks: false,
                 // Abort the XHR polling if one of the below errors were encountered
                 abortOn: ['error', 'timeout', 'parsererror'],
                 // Disable pagination resetting on updates ("true" will send the viewer
                 // to the first page every update)
                 resetPaging: false
             }
         })
         /**
          * Event:       xhrErr.liveAjax
          * Description: Triggered for any and all errors encountered during an XHR request (Meaning it covers
          *              all of the xhrErr*.liveAjax events below)
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErr.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErr', 'General XHR Error: ' + thrown);
         })
         /**
          * Event:       xhrErrTimeout.liveAjax
          * Description: Triggered when a 'timeout' error was thrown from an XHR request
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrTimeout.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErrTimeout', 'XHR Error: Timeout');
         })
         /**
          * Event:       xhrErrError.liveAjax
          * Description: Triggered when a 'error' error was thrown from an XHR request
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrError.liveAjax', function(e, settings, xhr, thrown) {
             console.log('XHR Error: Error');
         })
         /**
          * Event:       xhrErrAbort.liveAjax
          * Description: Triggered when an 'abort' error was thrown from an XHR request
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrAbort.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErrAbort', 'XHR Error: Abort');
         })
         /**
          * Event:       xhrErrParseerror.liveAjax
          * Description: Triggered when a 'parsererror' error was thrown from an XHR request
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrParseerror.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErrParseerror', 'XHR Error: Parse Error');
         })
         /**
          * Event:       xhrErrUnknown.liveAjax
          * Description: Triggered when an unknown error was thrown from an XHR request, this shouldn't ever
          *              happen actually, seeing as how all the textStatus values from
          *              http://api.jquery.com/jquery.ajax/ were accounted for. But I just liked having a default
          *              failsafe, in the case maybe a new error type gets implemented and this plugin doesn't get
          *              updated
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Error thrown
          */
         .on('xhrErrUnknown.liveAjax', function(e, settings, xhr, thrown) {
             console.log('xhrErrParseerror', '(Unknown) XHR Error: ' + thrown);
         })
         /**
          * Event:       xhrSkipped.liveAjax
          * Description: Triggered when an XHR iteration is skipped, either due to polling being paused, or an XHR request is already processing
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object; {string} Reason for skip (either 'paused' or 'processing')
          */
         .on('xhrSkipped.liveAjax', function(e, settings, reason) {
             console.log('xhrSkipped', 'XHR Skipped because liveAjax is ' + reason);
         })
         /**
          * Event:       setInterval.liveAjax
          * Description: Triggered when the setTimeout interval has been changed
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('setInterval.liveAjax', function(e, settings, interval) {
             console.log('setInterval', 'XHR polling interval set to ' + interval);
         })
         /**
          * Event:       init.liveAjax
          * Description: Triggered when the liveAjax plugin has been initialized
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('init.liveAjax', function(e, settings, xhr) {
             console.log('init', 'liveAjax initiated');
         })
         /**
          * Event:       clearTimeout.liveAjax
          * Description: Triggered when the timeout has been cleared, killing the XHR polling
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('clearTimeout.liveAjax', function(e, settings, xhr) {
             console.log('clearTimeout', 'liveAjax timeout cleared');
         })
         /**
          * Event:       abortXhr.liveAjax
          * Description: Triggered when the current XHR request was aborted, either by an API method or an internal reason (Not the same as 'xhrErrAbort.liveAjax')
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('abortXhr.liveAjax', function(e, settings, xhr) {
             console.log('abortXhr', 'liveAjax XHR request was aborted');
         })
         /**
          * Event:       setPause.liveAjax
          * Description: Triggered when the liveAjax XHR polling was paused or un-paused
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} XHR Object
          */
         .on('setPause.liveAjax', function(e, settings, paused) {
             console.log('setPause', 'liveAjax XHR polling was ' + (paused === true ? 'paused' : 'un-paused'));
         })
         /**
          * Event:       onUpdate.liveAjax
          * Description: Triggered when liveAjax is finished comparing the new/existing JSON, and has implemented any changes to the table, according to the new JSON data
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} Updates that were implemented; {object} New JSON data for tabke; {object} XHR Object
          */
         .on('onUpdate.liveAjax', function(e, settings, updates, json, xhr) {
             console.log('onUpdate', 'JSON Processed - Table updated with new data; ' + (updates.delete.length || 0) + ' deletes, ' + (updates.create.length || 0) + ' additions, ' + Object.keys(updates.update).length + ' updates');
         })
         /**
          * Event:       noUpdate.liveAjax
          * Description: Triggered when liveAjax is finished comparing the new/existing JSON, and no updates were implemented
          * Parameters:  {object} JQ Event; {object} DataTable Settings; {object} New JSON data for tabke; {object} XHR Object
          */
         .on('noUpdate.liveAjax', function(e, settings, json, xhr) {
             console.log('noUpdate', 'JSON Processed - Table not updated, no new data');
         });
     setInterval(function() {
         var status = localStorage.getItem("statusPage");
         if (status == 'Offline') {
             table.liveAjax.pause();
         }
     }, 1000);
     return table;
 }