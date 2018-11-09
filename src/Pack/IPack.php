<?php
namespace Air\Pack;

interface IPack
{
    function encode($buffer);
    function decode($buffer);

    function pack($data, $topic = null);
    function unPack($data);

    function getProBufSet();
}