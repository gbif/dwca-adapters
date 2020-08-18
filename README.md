# Darwin Core Archive Adapters

This PHP project provides converters for several checklist datasets from their native, proprietary and public format to a standard Darwin Core Archive
which can be indexed by GBIF ChecklistBank. The following sources are supported:

- usda: [USDA Plants](https://www.gbif.org/dataset/705922f7-5ba5-49ab-a75d-722e3090e690)
- tol: [Tree Of Life](https://www.gbif.org/dataset/41efd0ac-0c70-48af-9e38-b19c66d6f3e2)
- grin: [GRIN](https://www.gbif.org/dataset/66dd0960-2d7d-46ee-a491-87b9adcfe7b1)
- ([NCBI](https://www.gbif.org/dataset/fab88965-e69d-4491-a04d-e3198b626e52) has been replaced by https://github.com/gbif/ncbi-dwca.)

*The source code was created by Michael Giddens (mikegiddens@silverbiology.com), contracted by GBIF in 2010.*

# Installation

## Requirements
- PHP 5.2.x+

## Configuration
1) Copy the ```default.config.php``` to ```config.php``` and edit the information.

# Usage

1. cd into root folder of this project
2. execute ```php index.php {source}```
3. generated DWC archives will be in the output subfolder

### GBIF installation + Docker use (2019)

```shell
make

mkdir output
docker run -it --rm --user $(id -u):$(id -g) -v $PWD/output:/dwca-adapters/output docker.gbif.org/dwca-adapters
```

The adapters are installed on the [build server](https://builds.gbif.org/job/dwca-adapters/), which runs the jobs weekly.
The finished archives are copied to https://hosted-datasets.gbif.org/datasets/.
