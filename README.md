EntityBundle
============

Adding the twig templates
-------------------------
```Yaml
# Twig Configuration
twig:
    debug:            "%kernel.debug%"
    strict_variables: "%kernel.debug%"
    form:
        resources:
            - 'HnEntityBundle:Form:entity_plus.html.twig'
            - 'HnEntityBundle:Form:delete.html.twig'
```
