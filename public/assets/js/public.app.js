/*! TokenLite v1.1.3 | Copyright by Softnio. */
(function(a){var b=a(".page-overlay");a.ajaxSetup({headers:{"X-CSRF-TOKEN":a("meta[name=\"csrf-token\"]").attr("content")}}),a(document).ajaxStart(function(){b.addClass("is-loading")}),a(document).ajaxStop(function(){b.removeClass("is-loading")});var c=a(".document-type");0<c.length&&c.on("click",function(){var b=a(this).data("title"),c=a(".doc-upload-d2"),d="undefined"!=typeof a(this).data("change"),e=a(this).data("img");a(".doc-type-name").text(b),a("._image").attr("src",e),0<c.length&&d?c.removeClass("hide"):c.addClass("hide")});var d=a("form#kyc_submit");0<d.length&&ajax_form_submit(d,!1);var e=a(".upload-zone");if(0<e.length){Dropzone.autoDiscover=!1;var f=a("input#file_uploads").val(),g=a("meta[name=\"csrf-token\"]").attr("content"),h=".document_one";if(0<a(h).length){var i=new Dropzone(h,{url:f,uploadMultiple:!1,maxFilesize:5.1,maxFiles:1,addRemoveLinks:!0,acceptedFiles:"image/jpeg,image/png,application/pdf",hiddenInputContainer:".hiddenFiles",paramName:"kyc_file_upload",headers:{"X-CSRF-TOKEN":g}});i.on("sending",function(a,b,c){c.append("docType","doc-one")}).on("success",function(b,c){cl(c);var d=c.message;"error"==c.msg?(alert(d),i.removeFile(b)):a("input[name=\"document_one\"]").val(c.file_name)}).on("removedfile",function(){var b=a("input[name=\"document_one\"]").val();0<b.length&&a.post(f,{_token:csrf_token,action:"delete",file:b}).done(b=>{cl(b),a("input[name=\"document_one\"]").val("")})})}if(0<a(".document_two").length){var j=new Dropzone(".document_two",{url:f,uploadMultiple:!1,maxFilesize:5.1,maxFiles:1,addRemoveLinks:!0,acceptedFiles:"image/jpeg,image/png,application/pdf",hiddenInputContainer:".hiddenFiles",paramName:"kyc_file_upload",headers:{"X-CSRF-TOKEN":g}});j.on("sending",function(a,b,c){c.append("docType","doc-two")}).on("success",function(b,c){cl(c);var d=c.message;"error"==c.msg?(alert(d),j.removeFile(b)):a("input[name=\"document_two\"]").val(c.file_name)}).on("removedfile",function(){var b=a("input[name=\"document_two\"]").val();0<b.length&&a.post(f,{_token:csrf_token,action:"delete",file:b}).done(b=>{cl(b),a("input[name=\"document_two\"]").val("")})})}if(0<a(".document_upload_hand").length){var k=new Dropzone(".document_upload_hand",{url:f,uploadMultiple:!1,maxFilesize:5.1,maxFiles:1,addRemoveLinks:!0,acceptedFiles:"image/jpeg,image/png,application/pdf",hiddenInputContainer:".hiddenFiles",paramName:"kyc_file_upload",headers:{"X-CSRF-TOKEN":g}});k.on("sending",function(a,b,c){c.append("docType","doc-hand")}).on("success",function(b,c){cl(c);var d=c.message;"error"==c.msg?(alert(d),k.removeFile(b)):a("input[name=\"document_image_hand\"]").val(c.file_name)}).on("removedfile",function(){var b=a("input[name=\"document_image_hand\"]").val();0<b.length&&a.post(f,{_token:csrf_token,action:"delete",file:b}).done(b=>{cl(b),a("input[name=\"document_image_hand\"]").val("")})})}}})(jQuery);