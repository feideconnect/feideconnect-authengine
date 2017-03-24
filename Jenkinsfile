node {
    checkout scm

    stage 'Test'
    wrap([$class: 'AnsiColorBuildWrapper', 'colorMapName': 'XTerm']) {
        sh "./run-tests.sh"
    }

    junit "build/logs/junit.xml"
    step([$class: 'CheckStylePublisher', canComputeNew: false, defaultEncoding: '', healthy: '', pattern: 'build/logs/checkstyle.xml', unHealthy: ''])
    step([$class: 'PmdPublisher', canComputeNew: false, defaultEncoding: '', healthy: '', pattern: 'build/logs/pmd.xml', unHealthy: ''])
    step([$class: 'DryPublisher', canComputeNew: false, defaultEncoding: '', healthy: '', pattern: 'build/logs/pmd-cpd.xml', unHealthy: ''])
    publishHTML([allowMissing: false, alwaysLinkToLastBuild: false, keepAll: false, reportDir: 'build/coverage', reportFiles: 'index.html', reportName: 'Code coverage'])

    image_name = ""
    if (env.BRANCH_NAME == "master") {
        image_name = "registry.uninett.no/public/dataporten-auth-engine-dev"
    }
    if (env.BRANCH_NAME == "stable") {
        image_name = "registry.uninett.no/public/dataporten-auth-engine"
    }
    if (image_name != "") {
        stage 'Build'
        args = "--pull --no-cache --build-arg JENKINS_BUILD_NUMBER='${env.BUILD_NUMBER}' ."
        image = docker.build image_name, args
        image.push()
        image.push "latest"
    }
    if (env.BRANCH_NAME == "master") {
        stage "Deploy"
        sh "ssh jenkins@vltrd086.web.uninett.no auth-engine-dev"
    }
}
