module.exports = class {

	onDataLoad(props) {
		console.log(props)
		return {
			handleAccidentType: this.fetchQL('handleAccidentType', {
				atFatherId: props.item.atId,
			}),
			handleAccidentTypeDetails: this.dataQL('handleAccidentTypeDetails'),
			data: [],
			item: props.item,
		};
	}

	onLoaded() {
		let data = this.state.data;
		if (this.state.handleAccidentType.data.length) {
			data = this.state.handleAccidentType.data;
		} else {
			data.push(this.state.item);
		}
		this.setState({
			data: data,
		})
	}

	goBack() {
		padapp.navigate.pop();
	}

	show

}