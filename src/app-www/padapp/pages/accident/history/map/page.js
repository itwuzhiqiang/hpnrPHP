module.exports = class {

	onDataLoad() {
		return {
			startPoint: {lng: 106.557220, lat: 29.570656},
		};
	}


	setAddree(e) {
		console.log(e.point);
		padapp.app.emit('/handle.desc.map', {
			data: e,
		});
		padapp.navigate.pop();
	}

	goHistory(){
		padapp.navigate.pop();
	}

}