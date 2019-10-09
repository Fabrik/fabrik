Instructions to make commits, create and merge branches.
==========================================

This document document padronizes the way to do commits create and merge branches. 
The pattern used in this project is git flow, the main pattern used in most open source projects.

Branches
--------
When using git flow are needed some branches in the repo, first we have our master branch, here we put all the tested code, from that branch we compile the releases for fabrik, the other most important branch is develop, develop are the branch with the most recent code, there will happen all the development workflow, for each feature to be implemented, we create one branch, and when this feature are full implemented and ready to testing, you can merge that feature branch with develop branch, afer testing the code and all other features of that release are implemented we merge the develop branch with the master and create the release, for more details about that, check the references.


Commits
----
All the commit messages need to be done in English.

References:
-----------
In Portuguese:
https://medium.com/trainingcenter/utilizando-o-fluxo-git-flow-e63d5e0d5e04

In English:
https://jeffkreeftmeijer.com/git-flow/


created by ~rz in 09/10/2019
