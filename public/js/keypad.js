const updateInput = (e) => {
	const input = document.querySelector("#code");
	input.value = input.value.toString() + e.currentTarget.value.toString();
}

const deleteChar = (e) => {
	const input = document.querySelector("#code");
	const value = input.value;
	const new_value = value.slice(0, -1);
	input.value = new_value;
}
