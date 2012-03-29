<?php
class NxtbusApiEndPoints {
    const ROUTE_LIST = "http://webservices.nextbus.com/service/publicXMLFeed?command=routeList&a=";
    const ROUTE_CONFIG = "http://webservices.nextbus.com/service/publicXMLFeed?command=routeConfig&verbose=1&a=";

    //Emery go round still uses a private API
    const EMERY_ROUTE_LIST = "http://www.nextbus.com/s/COM.NextBus.Servlets.XMLFeed?command=routeList&a=";
    const EMERY_ROUTE_CONFIG = "http://www.nextbus.com/s/COM.NextBus.Servlets.XMLFeed?command=routeConfig&verbose=1&a=";
}
