module.exports = class extends React.Component {
	constructor(props) {
		super(props);
	}

	onclick(url) {
		this.props.onClick(url);
	}

	render() {
		if (this.props.imgPath) {
			return (
				<pxml>
					<image onClick="{(e)=>this.onclick(this.props.imgPath)}" class="imgpatch" src="{this.props.imgPath}">
					</image>
				</pxml>
			)
		} else if (this.props.imgPathMax) {
			return (
				<pxml>
					<image class="imgPatch-max" src="{this.props.imgPathMax}">
					</image>
				</pxml>
			)
		} else if (this.props.button) {
			return (
				<pxml>
					<link onClick="{(e)=>this.onclick('')}" class="operate">
						<text class="fs13 fc">{this.props.button}</text>
					</link>
				</pxml>
			)
		} else {
			return (
				<pxml>
					<text class="fs13 lightcolor">{this.props.content}</text>
				</pxml>
			);
		}
	}
};

