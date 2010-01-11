  <style type="text/css">
                  body {
                                margin:0;
                                padding:0;
                        }

                        .th {
                                background: url(http://yui.yahooapis.com/2.5.0/build/assets/skins/sam/sprite.png)  repeat-x 0 0;
                        }

                        #rmtools {
                                height:30em;
                        }

                        label { 
                                display:block;
                                float:left;
                                width:45%;
                                clear:left;
                        }

                        .clear {
                                clear:both;
                        }

                        #resp {
                                margin:10px;
                                padding:5px;
                                border:1px solid #ccc;
                                background:#fff;
                        }

                        #resp li {
                                font-family:monospace
                        }

                        .yui-pe .yui-pe-content {
                                display:none;
                        }
  </style>
  <link rel="stylesheet" type="text/css" href=
  "http://yui.yahooapis.com/2.8.0r4/build/fonts/fonts-min.css">
  <link rel="stylesheet" type="text/css" href=
  "http://yui.yahooapis.com/2.8.0r4/build/button/assets/skins/sam/button.css">
  <link rel="stylesheet" type="text/css" href=
  "http://yui.yahooapis.com/2.8.0r4/build/container/assets/skins/sam/container.css">

  <script type="text/javascript" src=
  "http://yui.yahooapis.com/2.8.0r4/build/yahoo-dom-event/yahoo-dom-event.js">
</script>
  <script type="text/javascript" src=
  "http://yui.yahooapis.com/2.8.0r4/build/connection/connection-min.js">
</script>
  <script type="text/javascript" src=
  "http://yui.yahooapis.com/2.8.0r4/build/element/element-min.js">
</script>
  <script type="text/javascript" src=
  "http://yui.yahooapis.com/2.8.0r4/build/button/button-min.js">
</script>
  <script type="text/javascript" src=
  "http://yui.yahooapis.com/2.8.0r4/build/dragdrop/dragdrop-min.js">
</script>
  <script type="text/javascript" src=
  "http://yui.yahooapis.com/2.8.0r4/build/container/container-min.js">
</script>
  <script src=
  "http://yui.yahooapis.com/2.8.0/build/yuiloader/yuiloader-min.js"
  type="text/javascript">
</script>
  <script type="text/javascript">

  (function () {
        var loader = new YAHOO.util.YUILoader();
        loader.loadOptional = true;
        loader.filter = 'raw';
        loader.require("reset-fonts-grids","base","datatable","calendar", "element", "connection", "button", "container", "json");
        loader.insert({ 
                onSuccess: function() {
                        this.myCustomStatusFormatter = function(elLiner, oRecord, oColumn, oData) {

                                switch (parseInt(oRecord.getData("status"))) {
                                        // Merged
                                        case 1:
                                                elLiner.innerHTML = ' <img src="/images/merged_small.png" width="30" />';
                                                break;
                                        // Rejected
                                        case -1:
                                                elLiner.innerHTML = ' <img src="/images/rejected_small.png" width="30"/>';
                                                break;
                                        // Open 
                                        case 2:
                                                elLiner.innerHTML = '  <img src="/images/question_small.png" width="30"/>';
                                                break;
                                        default:
												elLiner.innerHTML = ' ';

                                }
                        };

                        this.myCustomRevisionFormatter = function(elLiner, oRecord, oColumn, oData) {
                                elLiner.innerHTML = '<a href="http://svn.php.net/viewvc?view=revision&revision=' + oRecord.getData('revision') + '">' + oRecord.getData('revision') + '</a>';
                        };

                        // Add the custom formatter to the shortcuts
                        YAHOO.widget.DataTable.Formatter.status = this.myCustomStatusFormatter;
                        YAHOO.widget.DataTable.Formatter.revision = this.myCustomRevisionFormatter;


                        var myColumnDefs = [
                                {key:"Rows",label:'&nbsp;',className:'th'},
                                {key:"revision", sortable:true, formatter:"revision"},
                                {key:"date", sortable:true, formatter:"date"},
                                {key:"author", sortable:true},
                                {key:"msg", resizeable: true},
                                {key:"status", sortable:true, formatter:"status"},
                                {key:"comment",resizeable: true, width:300},
                                {key:"news", resizeable: true, width:300}
                        ];

                        var ds = new YAHOO.util.DataSource("/rm/json.php?release=<?php echo $release_name; ?>"); 
                                ds.responseType = YAHOO.util.DataSource.TYPE_JSON; 
                                ds.connXhrMode = "queueRequests"; 
                                ds.responseSchema = { 
                                        resultsList: "data", 
                                        fields: [
                                        {key:"revision"},
                                        {key:"date"},
                                        {key:"author"},
                                        {key:"msg"},
                                        {key:"status"},
                                        {key:"comment"},
                                        {key:"news"}
                                        ],
                                        metaFields: {
                                                totalRecords: "totalRecords"
                                        }
                                }; 

                        var dt = new YAHOO.widget.DataTable("tableContainer", myColumnDefs, ds, {selectionMode:"single"});


                        // Set up editing flow 
                var showRowEditor = function(oArgs) {
                                var el = oArgs.el,
                                        record = oArgs.record,
                                        data = record.getData(),
                                        revision = record.getData('revision'),
                                        msg = record.getData('msg'),
                                        author = record.getData('author'),
                                        comment = record.getData('comment'),
                                        news = record.getData('news'),
                                        status = record.getData('status'),
                                        dialog_form = YAHOO.rmtools.container.editRowDialog.form;

                                        document.getElementById("meta").innerHTML = "<label>Revision<\/label>" + revision + "<br />" + "<label>Author<\/label>" + author + "<br />" + "<label>Message<\/label>" + msg;
                                        dialog_form.elements[0].value = comment;
                                        dialog_form.elements[1].value = news;
                                        dialog_form.revision.value = revision;

                                        if (status == 1) {
                                                dialog_form.status[0].checked = true;
                                                dialog_form.status[1].checked = false;
                                                dialog_form.status[2].checked = false;
                                        } else if (status == -1) {
                                                dialog_form.status[0].checked = false;
                                                dialog_form.status[1].checked = true;
                                                dialog_form.status[2].checked = false;
                                        } else if (status == 2) {
                                                dialog_form.status[0].checked = false;
                                                dialog_form.status[1].checked = false;
                                                dialog_form.status[2].checked = true;
                                        } else {
                                                dialog_form.status[0].checked = false;
                                                dialog_form.status[1].checked = false;
                                                dialog_form.status[2].checked = false;
										}

                                        YAHOO.rmtools.container.editRowDialog.show();
                                        YAHOO.rmtools.container.editRowDialog.record = record;
                                        YAHOO.rmtools.container.editRowDialog.dt = dt;
                };

                        //dt.onEventSelectRow = showRowEditor;
                dt.subscribe("rowMouseoverEvent", dt.onEventHighlightRow); 
                dt.subscribe("rowMouseoutEvent", dt.onEventUnhighlightRow); 
                dt.subscribe("rowClickEvent", dt.onEventSelectRow); 
                dt.subscribe("rowSelectEvent", showRowEditor);

                }
        });
  })();
  document.documentElement.className = "yui-pe";
  </script>
