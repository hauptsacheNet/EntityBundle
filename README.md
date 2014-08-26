EntityBundle
============

Adding the twig templates
-------------------------
```Yaml
# app/config/config.yml
twig:
    debug:            "%kernel.debug%"
    strict_variables: "%kernel.debug%"
    form:
        resources:
            - 'HnEntityBundle:Form:entity_plus.html.twig'
            - 'HnEntityBundle:Form:delete.html.twig'
```
Setting the routes
------------------
```Yaml
# app/config/routing.yml
hn_entity:
    resource: "@HnEntityBundle/Controller/"
    type:     annotation
    prefix:   /entity
```
