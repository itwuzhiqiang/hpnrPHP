module.exports = class {

	onDataLoad() {
		return {
			handleTemplateGet: this.fetchQL('handleTemplateGet'),
            dataServer: padapp.app.getConfig('rpapi'),
            policeInfo: this.fetchQL('policeInfo'),
		};
	}

	goBack() {
		padapp.navigate.pop();
	}

	goChooseTemplate(item) {
		padapp.app.emit('/handle.responsibility.template', {
			data: item,
		});
		padapp.navigate.pop()
	}
    onLoaded() {
        let that = this;
        let currenturl = window.location.href;
        $.ajax({
            url:that.state.dataServer + '/accident/v1/app/accident/history/addMHistoryRecord?html=' + currenturl + '&accountToken='  + that.state.policeInfo.data.sign,
            type:'POST',
            data: currenturl,
            cache: false,
            processData: false,
            contentType: false
        }).done((res)=>{
            console.log("success");
        }).fail(function (ress) {
            console.log("fail");

        })

    }

}