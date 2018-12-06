module.exports = class {

	onDataLoad(props) {
		return {
			startPoint: JSON.stringify(props.startPoint) == '{}' ? {} : props.startPoint,
		};
	}

	onLoaded() {
		console.log(this.state.startPoint);
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


	setAddree(e) {
		padapp.app.emit('/handle.desc.map', {
			data: e,
		});
		padapp.navigate.pop();
	}

	goBack() {
		padapp.navigate.pop()
	}

}