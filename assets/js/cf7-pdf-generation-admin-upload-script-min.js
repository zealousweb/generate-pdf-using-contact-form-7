function ValidateSize(e) {
    var r = e.files[0],
        a = document.getElementById("cf7_opt_attach_pdf_image").value;
    return "pdf" != a.substring(a.lastIndexOf(".") + 1)
        ? (jQuery("#upload-pdf-err").text("Please attach PDF file only."), jQuery("#upload-pdf-err").show(), !1)
        : r.size > 26214400
        ? (jQuery("#upload-pdf-err").text("File size should be less than 25MB!"), jQuery("#upload-pdf-err").show(), !1)
        : (jQuery("#upload-pdf-err").hide(), !0);
}
jQuery(document).ready(function () {
    var e = 0;
    jQuery("#cf7_opt_upload_image_button").click(function () {
        tb_show("", "media-upload.php?type=image&amp;TB_iframe=true"),
            (window.send_to_editor = function (e) {
                if (e.match(/<img/)) {
                    if (((imgurl = jQuery(e).attr("src")), jQuery("#cf7_opt_upload_image").val(imgurl), imgurl)) {
                        var r = imgurl.lastIndexOf("."),
                            a = imgurl.substr(r + 1);
                        if ("gif" == a || "svg" == a) {
                            jQuery("#upload-header-logo-err").show();
                            var t = jQuery("#cf7_opt_upload_image_current").val();
                            jQuery("#cf7_opt_upload_image").val(t), jQuery("#upload-header-logo-err").text("Please select JPEG/PNG file only");
                        } else
                            jQuery("#upload-header-logo-err").hide(),
                                jQuery("#cf7_opt_dis_img").show(),
                                jQuery("#cf7_opt_upload_image_current").val(imgurl),
                                jQuery("#cf7_opt_dis_img").html('<img id="cf7_opt_display_image" src="' + imgurl + '" height="150px" width="200px" /><a class="close remove-upload-header-logo" href="#" ></a>');
                    } else jQuery("#cf7_opt_dis_img").hide(), jQuery("#cf7_opt_upload_image").val(""), jQuery("#upload-header-logo-err").text("Please select JPEG/PNG file only.");
                    tb_remove();
                } else
                    jQuery("#upload-header-logo-err").show(),
                        (t = jQuery("#cf7_opt_upload_image_current").val()),
                        jQuery("#cf7_opt_upload_image").val(t),
                        jQuery("#upload-header-logo-err").text("Please select JPEG/PNG file only"),
                        tb_remove();
            });
    });
    var r = jQuery("input[type=radio].cf7_opt_enable:checked").val();
    "true" == r ? (jQuery(".enable-pdf").show(), jQuery(".enable-pdf-link").show(), jQuery(".pdf-genrate").show()) : "false" == r && (jQuery(".disable-pdf-link").hide(), jQuery(".pdf-genrate").hide(), jQuery(".enable-pdf").hide(), jQuery(".enable-pdf-link").hide());
    var a = jQuery("input[type=radio].cf7_opt_attach_enable:checked");
    jQuery(a).each(function (e) {
        var r = jQuery(this).val();
        "true" == r ? jQuery(".pdf-attach").show() : "false" == r && jQuery(".pdf-genrate").show();
    }),
        jQuery(".cf7_pdf_link_enable")
            .change(function () {
                "true" == jQuery("input[type=radio].cf7_pdf_link_enable:checked").val() ? jQuery("#onsent_mail_pdfopt").show() : jQuery("#onsent_mail_pdfopt").show();
            })
            .change(),
        jQuery(".cf7_opt_attach_enable")
            .change(function () {
                var r = jQuery("input[type=radio].cf7_opt_attach_enable:checked").val();
                if ("true" == r) jQuery(".pdf-attach").show(), jQuery(".pdf-genrate").hide();
                else if ("false" == r) {
                    if (0 == e) {
                        e = 1;
                        var a = document.getElementById("code");
                        if (null != a) {
                            var t = CodeMirror.fromTextArea(a, { mode: "htmlmixed", lineNumbers: !0, theme: "3024-night", autofocus: !0 });
                            t.save(),
                                setTimeout(function () {
                                    t.refresh();
                                }, 300),
                                jQuery(".CodeMirror").resizable({
                                    resize: function () {
                                        t.setSize(jQuery(this).width(), jQuery(this).height());
                                    },
                                });
                        }
                    }
                    jQuery(".pdf-genrate").show(), jQuery(".pdf-attach").hide();
                }
            })
            .change(),
        jQuery(".cf7_opt_enable").change(function () {
            "true" == this.value ? (jQuery(".enable-pdf").show(), jQuery(".enable-pdf-link").show(),jQuery(".disable-pdf-link").show()) : "false" == this.value && (jQuery(".enable-pdf").hide(), jQuery(".enable-pdf-link").hide(), jQuery(".disable-pdf-link").hide());
        }),
        jQuery(document).on("click", ".remove-upload-header-logo", function () {
            if (1 != confirm("Are you sure want to delete the header logo ?")) return !1;
            jQuery("#cf7_opt_dis_img").hide(), jQuery("#cf7_opt_upload_image").val("");
        }),
        jQuery(document).on("click", ".remove-upload-pdf", function () {
            if (1 != confirm("Are you sure want to delete the PDF file ?")) return !1;
            jQuery(".upload-pdf-file-block").hide(), jQuery("#cf7_opt_attach_pdf_old_url").val("");
        }),
        jQuery(document).on("click", ".cf7-pdf-submit", function () {
            if ("true" == jQuery("input[type=radio].cf7_opt_attach_enable:checked").val()) {
                if (("" == jQuery("#cf7_opt_attach_pdf_old_url").val() && jQuery("#cf7_opt_attach_pdf_image").attr("required", !0), jQuery("#cf7_opt_attach_pdf_image").val())) {
                    var e = document.getElementById("cf7_opt_attach_pdf_image").value,
                        r = e.substring(e.lastIndexOf(".") + 1),
                        a = jQuery("#cf7_opt_attach_pdf_image")[0].files[0].size;
                    return "pdf" != r
                        ? (jQuery(".upload-pdf-err").text("Please attach PDF file only."), jQuery("#upload-pdf-err").show(), !1)
                        : a > 26214400
                        ? (jQuery("#upload-pdf-err").text("File size should be less than 25MB!"), jQuery("#upload-pdf-err").show(), !1)
                        : (jQuery(".upload-pdf-err").hide(), !0);
                }
            } else jQuery("#cf7_opt_attach_pdf_image").attr("required", !1), jQuery("#cf7_opt_attach_pdf_image").val("");
        }),
        jQuery(document).on("click", "#wpbody", function (e) {
            jQuery(".cf7pap-pointer").hide();
        });
});
