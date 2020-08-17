DOCKER_IMAGE_NAME ?= dwca-adapters
DOCKER_IMAGE_NAME_TO_TEST ?= docker.gbif.org/dwca-adapters

all: build deploy

build:
	git clean -f -X
	docker build \
		-t $(DOCKER_IMAGE_NAME_TO_TEST) \
		-f Dockerfile \
		$(CURDIR)/

deploy:
	docker push $(DOCKER_IMAGE_NAME_TO_TEST)

.PHONY: all build deploy
