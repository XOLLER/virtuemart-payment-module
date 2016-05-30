all:
	if [[ -e bepaid.zip ]]; then rm bepaid.zip; fi
	zip -r bepaid.zip bepaid