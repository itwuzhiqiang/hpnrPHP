module.exports = class {

	onDataLoad(props) {
		return {
			show: true,
			data: props.data ? props.data : [],
			img: true,
		};
	}

	onLoaded() {
		let data = this.state.data;
		if (data.atPictureAddress === undefined) {
			this.setState({
				img: false,
			})
		};
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

	changeShow() {
		this.setState({
			show: !this.state.show,
		})
	}

	goBack() {
		padapp.navigate.pop();
	}

}