  <div class="yui-skin-sam">
    <div id="hd">
      <h1>PHP <?php echo $release_name; ?> Merges (updated on <?php echo $release['last_update'];?>)</h1>
    </div>

    <div id="bd" style="margin:2em">
      <div id="tableContainer"></div>
    </div>

    <div id="cellediting"></div><script type="text/javascript">
YAHOO.namespace("rmtools.container");

    YAHOO.util.Event.onDOMReady(function () {
        
        // Define various event handlers for Dialog
        var handleSubmit = function() {
                this.submit();
        };
        var handleCancel = function() {
                this.cancel();
        };
        var handleSuccess = function(o) {
                var response = o.responseText;
                var record = YAHOO.rmtools.container.editRowDialog.record;
                var dt = YAHOO.rmtools.container.editRowDialog.dt;
                var return_data = YAHOO.lang.JSON.parse(response);
                var new_data = record.getData();
                response = response.split("<!")[0];
                document.getElementById("resp").innerHTML = response;
                dt.updateRow(record, return_data);
        };

        var handleFailure = function(o) {
                alert("Submission failed: " + o.status + "\nResponse: " + o.responseText);
        };

		// Remove progressively enhanced content class, just before creating the module
		YAHOO.util.Dom.removeClass("editRowDialog", "yui-pe-content");

        // Instantiate the Dialog
        YAHOO.rmtools.container.editRowDialog = new YAHOO.widget.Dialog("editRowDialog", 
                                                        { width : "40em",
                                                          fixedcenter : true,
                                                          visible : false, 
                                                          constraintoviewport : true,
                                                          buttons : [
                                                                {
                                                                        text:"Submit",
                                                                        handler:handleSubmit,
                                                                        isDefault:true
                                                                },
                                                                {
                                                                        text:"Cancel",
                                                                        handler:handleCancel
                                                                }
                                                        ]
                                                });

        YAHOO.rmtools.container.editRowDialog.callback = {
                                                                success: handleSuccess,
                                                                failure: handleFailure
                                                        };

        YAHOO.rmtools.container.editRowDialog.render();
    });
    </script>

    <div id="resp">
      Debug area for server responses
    </div>

    <div id="editRowDialog" class="yui-pe-content">
      <div class="hd">
        Please enter your information
      </div>

      <div class="bd">
        <form method="post" action=
        "index.php?mode=edit&json=1&release=<?php echo $release_name;?>">
          <div id="meta"></div><label for=
          "comment">Comment:</label> 
          <textarea name="comment">
</textarea> <label for="news">News:</label> 
          <textarea name="news">
</textarea>

          <div class="clear"></div><label for=
          "status">Status:</label> <input type="radio" name=
          "status" value="1"> Merged <input type="radio" name=
          "status" value="-1"> Rejected <input type="radio" name=
          "status" value="2"> Open <input type="hidden" name=
          "revision" value=""> <input type="hidden" name=
          "recordindex" value="">

          <div class="clear"></div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
