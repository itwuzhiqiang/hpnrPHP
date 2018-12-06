module.exports = class {

	onDataLoad(props) {
		return {
			val: props.address || '',
		};
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

	setVal(e) {
		let val = e.target.value;
		this.setState({
			val: val,
		})
	}

	goSure() {
		let val = this.state.val;
		padapp.app.emit('/handle.duty.accident', {
			val: val,
		});
		padapp.navigate.pop()
	}

	goBack() {
		padapp.navigate.pop()
	}

}